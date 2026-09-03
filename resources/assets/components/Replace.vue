<template>
  <div class="block-attachment--modal" v-if="open && attachment.id">
    <div @click="close" class="block-attachment__close">
      <i class="material-icons"> clear </i>
    </div>
    <div class="block-attachment__content">
      <div class="block-attachment__replace block block--white text-left">

        <h4 class="mb-3">Remplacer le fichier</h4>

        <!-- FICHIER ACTUEL -->
        <ul class="list-unstyled mb-3">
          <li><strong>Fichier actuel</strong> : {{ attachment.name }}</li>
          <li v-if="attachment.size"><strong>Poids</strong> : {{ attachment.size | bytesToMegaBytes | decimal(2) }} MB</li>
          <li v-if="attachment.modified">
            <strong>Modifié le</strong> :
            {{ new Date(attachment.modified).toLocaleDateString('fr-CH') }}
          </li>
        </ul>

        <!-- CE QUE LE REMPLACEMENT GARANTIT -->
        <p class="small mb-3">
          Le nouveau fichier est écrit à la même adresse que l'ancien :
          <strong>l'URL ne change pas et tous les liens existants restent valables</strong>,
          sur le site comme dans les documents déjà diffusés.
        </p>

        <input type="file" ref="fileInput" @change="pickFile" style="display: none">

        <!-- CHOIX DU FICHIER -->
        <div v-if="!newFile">
          <button type="button" class="btn btn--green color--white" @click="$refs.fileInput.click()">
            Choisir le nouveau fichier
          </button>
        </div>

        <!-- CONFIRMATION -->
        <div v-else>
          <ul class="list-unstyled mb-2">
            <li><strong>Nouveau fichier</strong> : {{ newFile.name }}</li>
            <li><strong>Poids</strong> : {{ (newFile.size / 1024 / 1024).toFixed(2) }} MB</li>
          </ul>

          <!-- L extension fait partie du chemin reutilise : un type different
               produirait une URL trompeuse (.pdf servant une image). -->
          <p v-if="typeMismatch" class="small color--red mb-2">
            <strong>Attention</strong> : le nouveau fichier n'est pas du même type que l'ancien
            ({{ newFile.type || 'type inconnu' }} au lieu de {{ currentMime }}).
            L'adresse du fichier étant conservée, elle continuera de se terminer
            par « .{{ currentExtension }} ». Vérifiez que c'est bien ce que vous voulez.
          </p>

          <p v-for="(e, i) in errors" :key="i" class="small color--red mb-2">{{ e }}</p>

          <div v-if="uploading" class="progress mb-2">
            <div class="progress-bar" role="progressbar" :style="{width: progress + '%'}">{{ progress }}%</div>
          </div>

          <div class="btn-group">
            <button type="button" class="btn btn--green color--white"
              :disabled="uploading || errors.length > 0" @click="submit">
              Remplacer
            </button>
            <button type="button" class="btn btn--grey color--white" :disabled="uploading" @click="reset">
              Choisir un autre fichier
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { client } from '../http/client.js'

export default {
  name: 'replace',
  props: { aid: String },
  data()
  {
    return {
      open: false,
      newFile: null,
      errors: [],
      uploading: false,
      progress: 0
    }
  },
  computed:
  {
    attachment()
    {
      return this.$store.get(this.aid + '/replace')
    },
    settings()
    {
      return this.$store.get(this.aid + '/settings')
    },
    upload()
    {
      return (this.settings || {}).upload || {}
    },
    currentMime()
    {
      return this.attachment.type ? this.attachment.type + '/' + this.attachment.subtype : ''
    },
    currentExtension()
    {
      return (this.attachment.path || this.attachment.name || '').split('.').pop()
    },
    typeMismatch()
    {
      if (!this.newFile || !this.newFile.type || !this.currentMime) return false
      return this.newFile.type !== this.currentMime
    }
  },
  watch:
  {
    attachment: function()
    {
      this.reset()
      this.open = true
    }
  },
  methods:
  {
    pickFile(event)
    {
      this.errors = []
      const file = event.target.files[0]
      if (!file) return

      const maxsize = this.upload.maxsize
      if (maxsize && (file.size / 1024 / 1024) > maxsize) {
        this.errors.push(`Ce fichier est trop lourd (${(file.size / 1024 / 1024).toFixed(2)} MB). La taille maximale est de ${maxsize} MB.`)
      }

      const types = this.upload.types
      if (types && types.length > 0 && types.indexOf(file.type) === -1) {
        this.errors.push(`Ce type de fichier (${file.type || 'inconnu'}) n'est pas accepté.`)
      }

      this.newFile = file
    },
    reset()
    {
      this.newFile = null
      this.errors = []
      this.uploading = false
      this.progress = 0
      if (this.$refs.fileInput) this.$refs.fileInput.value = ''
    },
    close()
    {
      this.open = false
      this.reset()
      this.$store.set(this.aid + '/replace', {})
    },
    submit()
    {
      if (!this.newFile || this.errors.length > 0) return

      this.uploading = true
      this.progress = 0

      const data = new FormData()
      data.append('path', this.newFile)
      data.append('uuid', this.aid)

      client.post(
        `${this.settings.url}attachment/attachments/edit/${this.attachment.id}`,
        data,
        {
          headers: { 'Accept': 'application/json', 'Content-Type': 'multipart/form-data' },
          onUploadProgress: (e) => {
            if (e.lengthComputable) this.progress = Math.floor((e.loaded / e.total) * 100)
          }
        }
      ).then(this.done, this.failed)
    },
    done()
    {
      // Rafraichit la liste : le chemin est inchange, seule la date de
      // modification bouge, et c'est elle qui casse le cache des liens.
      this.$store.set(this.aid + '/aParams', Object.assign(
        this.$store.get(this.aid + '/aParams'),
        { refresh: new Date().getTime() }
      ))
      this.close()
    },
    failed()
    {
      this.uploading = false
      this.errors.push("Le remplacement a échoué. Le fichier d'origine n'a pas été modifié.")
    }
  }
}
</script>
