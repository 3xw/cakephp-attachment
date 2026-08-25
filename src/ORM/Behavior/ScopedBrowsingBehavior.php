<?php
declare(strict_types=1);

namespace Trois\Attachment\ORM\Behavior;

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * Restrict an Attachments query to the rows tagged with at least one of the
 * caller's "scope" atags.
 *
 * Scope dimensions are configured operator-side via
 * `Trois/Attachment.browse.user_filter_tag_types` (array of `atag_type_id`).
 * Only atags whose type is in that list count as scope dimensions — so an
 * unrelated personal type like the favourites "Sélection" never leaks into
 * the filter (this was the bug in the legacy `UserATagsBehavior`).
 *
 * Semantics (kept intentionally permissive for backward compat):
 *   - No identity in $options                → no-op (callers must opt in).
 *   - role ∈ {admin, superuser}              → no-op (privileged bypass).
 *   - `user_filter_tag_types` empty          → no-op (feature off).
 *   - User has zero atags of a scoped type   → no-op (full access).
 *   - Otherwise                              → id IN (pivot ⨝ userTagIds) subquery.
 *
 * The per-user lookup is cached for 60 s under the `scope` Cache config so
 * authenticated browsing doesn't hit the DB on every request.
 */
class ScopedBrowsingBehavior extends Behavior
{
    /** @var array<string, mixed> */
    protected array $_defaultConfig = [
        'cacheConfig' => 'scope',
        'cacheKeyPrefix' => 'scope:',
        // Roles bypassing the filter. Override per project if you add custom
        // privileged roles, but admins should always see everything.
        'bypassRoles' => ['admin', 'superuser'],
    ];

    public function beforeFind(EventInterface $event, SelectQuery $query, ArrayObject $options, bool $primary): void
    {
        $identity = $options['identity'] ?? null;
        if ($identity === null) {
            // Without an identity we have no user to scope to. The caller
            // chose not to pass one (CLI, internal task, unauthenticated
            // route) — let the query through untouched.
            return;
        }

        if ($this->_isBypassRole($identity)) {
            return;
        }

        $scopedTypeIds = (array)Configure::read('Trois/Attachment.browse.user_filter_tag_types');
        $scopedTypeIds = array_values(array_filter($scopedTypeIds, fn ($v) => $v !== null && $v !== ''));
        if (empty($scopedTypeIds)) {
            return; // feature off
        }

        $userId = $this->_extractUserId($identity);
        if ($userId === null) {
            return; // can't resolve user id from identity → don't lock the user out
        }

        $tagsIds = $this->_resolveUserScopeAtagIds($userId, $scopedTypeIds);
        if (empty($tagsIds)) {
            // Permissive default: user is not tied to any scope-typed atag
            // → no filter. Keeps existing users unaffected when the feature
            // is first switched on by the operator.
            return;
        }

        // Subquery on the pivot instead of matching('Atags'): the browse
        // filters (Search, TagOrRestricted) manually join `atags` under the
        // same `Atags` alias, and CakePHP keys joins by alias — matching()
        // would overwrite their join in place and produce an ON clause that
        // references the junction table before it is declared (SQL error
        // 1054). A subquery composes with any caller-side join.
        $alias = $this->table()->getAlias();
        $junction = $this->table()->getAssociation('Atags')->junction();
        $subquery = $junction->find()
            ->select([$junction->getAlias() . '.attachment_id'])
            ->where([$junction->getAlias() . '.atag_id IN' => $tagsIds]);
        $query->where([$alias . '.id IN' => $subquery]);
    }

    /**
     * Compute (and cache) the atag ids the user is scoped to, restricted to
     * the types listed in `user_filter_tag_types`. Public so the controller
     * layer can reuse the exact same logic when minting JWTs or warming the
     * cache — keeps the source of truth in one place.
     *
     * @param array<int, int|string> $scopedTypeIds
     * @return array<int, int>
     */
    public function resolveScopeForUser(string $userId, array $scopedTypeIds): array
    {
        return $this->_resolveUserScopeAtagIds($userId, $scopedTypeIds);
    }

    /**
     * @param array<int, int|string> $scopedTypeIds
     * @return array<int, int>
     */
    protected function _resolveUserScopeAtagIds(string $userId, array $scopedTypeIds): array
    {
        $cacheConfig = (string)$this->getConfig('cacheConfig');
        $key = $this->getConfig('cacheKeyPrefix') . $userId;

        $callback = function () use ($userId, $scopedTypeIds): array {
            $Users = TableRegistry::getTableLocator()->get('Users');
            $row = $Users->find()
                ->select(['Users.id'])
                ->where(['Users.id' => $userId])
                ->contain(['Atags' => function ($q) use ($scopedTypeIds) {
                    return $q
                        ->select(['Atags.id', 'Atags.atag_type_id'])
                        ->where(['Atags.atag_type_id IN' => $scopedTypeIds]);
                }])
                ->first();
            if ($row === null || empty($row->atags)) {
                return [];
            }
            $ids = [];
            foreach ($row->atags as $tag) {
                $ids[] = (int)$tag->id;
            }
            return array_values(array_unique($ids));
        };

        try {
            return Cache::remember($key, $callback, $cacheConfig);
        } catch (\Throwable) {
            // Cache config missing (e.g. test bootstrap) → fall back to a
            // direct read rather than crashing the whole browse pipeline.
            return $callback();
        }
    }

    protected function _isBypassRole(mixed $identity): bool
    {
        $role = null;
        if (is_object($identity) && method_exists($identity, 'getOriginalData')) {
            $data = $identity->getOriginalData();
            if (is_object($data)) {
                $role = $data->role ?? null;
            } elseif (is_array($data)) {
                $role = $data['role'] ?? null;
            }
        } elseif (is_array($identity)) {
            $role = $identity['role'] ?? null;
        } elseif (is_object($identity)) {
            $role = $identity->role ?? null;
        }
        if ($role === null) {
            return false;
        }
        $bypass = (array)$this->getConfig('bypassRoles');
        return in_array((string)$role, $bypass, true);
    }

    protected function _extractUserId(mixed $identity): ?string
    {
        if (is_object($identity) && method_exists($identity, 'getIdentifier')) {
            $id = $identity->getIdentifier();
            if ($id !== null && $id !== '') {
                return (string)$id;
            }
        }
        if (is_array($identity) && isset($identity['id'])) {
            return (string)$identity['id'];
        }
        if (is_object($identity)) {
            if (method_exists($identity, 'getOriginalData')) {
                $data = $identity->getOriginalData();
                if (is_object($data) && isset($data->id)) {
                    return (string)$data->id;
                }
                if (is_array($data) && isset($data['id'])) {
                    return (string)$data['id'];
                }
            }
            if (isset($identity->id)) {
                return (string)$identity->id;
            }
        }
        return null;
    }
}
