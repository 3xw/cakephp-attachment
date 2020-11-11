import axios from 'axios'

const
Http = axios.create({
  headers: {
    'X-CSRF-TOKEN'    : window.csrfToken,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

class client extends Http {}

const
parseResponse = function(response)
{
  return {
    data: response.data.data// expecting object with ID
  }
},
parseResponseWithPaginate = function(response)
{
  const { data } = response;

  return Object.assign({}, response, {
    data: data.data, // expecting array of objects with IDs
    pagination: data.pagination// expecting object with ID
  });
},
parseTags = function(response)
{
  let atagTypes = {}
  for(let i in response.data.data)
  {
    let atag = response.data.data[i]
    let type = atag.atag_type? atag.atag_type: {
      id: 0,
      name: 'Autres',
      exclusive: false,
      order: 1000
    }

    if(!atagTypes[type.name]) atagTypes[type.name] = Object.assign({atags: []}, type)
    atagTypes[type.name].atags.push(atag)
  }

  // sort
  let keysSorted = Object.keys(atagTypes).sort(function(a,b){return atagTypes[a].order-atagTypes[b].order})
  let types = []
  for(let i in keysSorted) types.push(atagTypes[keysSorted[i]])

  // return tags
  return {data: types}
}

export { client, parseResponse, parseResponseWithPaginate, parseTags }
