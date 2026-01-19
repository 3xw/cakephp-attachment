/* FILTERS
*******************************/
export function registerFilters(Vue) {
  Vue.filter('inputName', function (input) {
    return input.split('.').reduce(function(o,i){ return o+'['+i+']' ;});
  });

  Vue.filter('bytesToMegaBytes', function (input) {
    return input / 1024 / 1024;
  });

  Vue.filter('sqlDate', function(input) {
    return input.replace('T', '');
  });

  Vue.filter('decimal', function (input, places) {
    if (isNaN(input)) return input;
    var factor = "1" + Array(+(places > 0 && places + 1)).join("0");
    return Math.round(input * factor) / factor;
  });

  Vue.filter('truncate', function(value, length) {
    if(!value) return ''
    if(value.length < length) {
      return value;
    }

    length = length - 3;

    return value.substring(0, length) + '...';
  });

  Vue.filter('icon', function (attachment)
  {
    var input;
    if(!attachment.aarchive) input = attachment.type+'/'+attachment.subtype
    else
    {
      switch(attachment.aarchive.state)
      {
        case 'COMPLETED':
        input = 'aarchive/completed'
        break

        default:
        input = 'aarchive/progress'
      }
    }

    switch(true)
    {
      case input == 'aarchive/progress':
      return '<i class="material-icons rotating">cached</i>'
      break;
      case input == 'aarchive/completed':
      return '<i class="material-icons">cloud_done</i>'
      break;
      case input == 'image/jpeg':
      case input == 'image/png':
      case input == 'image/gif':
      return '<i class="material-icons">photo</i>';
      break;
      case input.lastIndexOf('embed') != -1:
      return '<i class="material-icons">code</i>';
      break;
      case input == 'application/pdf':
      return '<i class="material-icons">picture_as_pdf</i>';
      break;
      case input == 'video/mp4':
      case input == 'video/ogg':
      return '<i class="material-icons">videocam</i>';
      break;
      case input == 'audio/mp3':
      case input == 'audio/ogg':
      return '<i class="material-icons">audiotrack</i>';
      break;
      default:
      return '<i class="material-icons">insert_drive_file</i>';
    }
  });

  Vue.filter('isNiceImage', function (file) {
    switch(file.type+'/'+file.subtype){
      case 'image/jpeg':
      case 'image/png':
      case 'image/gif':
      return true;
      default:
      return false;
    }
  });

  Vue.filter('isEmbed', function (file) {
    switch(file.type){
      case 'embed':
      return true;
      break;
      default:
      return false;
    }
  });

  Vue.filter('isNotEmbed', function (file) {
    switch(file.type){
      case 'embed':
      return false;
      break;
      default:
      return true;
    }
  });

  Vue.filter('isThumbable', function (file, hasCustomThumbService = false) {
    switch(file.type+'/'+file.subtype){
      case 'image/jpeg':
      case 'image/png':
      case 'image/gif':
      return true;
      break;
      case 'video/mp4':
      case 'video/webm':
      case 'video/mov':
      case 'video/avi':
      case 'video/ogg':
      case 'video/mkv':
      case 'application/pdf':
      return hasCustomThumbService
      break;
      case 'embed/youtube':
      case 'embed/vimeo':
      return (file.path)? true : false;
      break;
      default:
      return false;
    }
  });

  Vue.filter('isNotThumbable', function (file) {
    switch(file.type+'/'+file.subtype){
      case 'image/jpeg':
      case 'image/png':
      case 'image/gif':
      return false;
      break;
      case 'embed/youtube':
      case 'embed/vimeo':
      return (file.path)? false : true;
      break;
      default:
      return true;
    }
  });
}
