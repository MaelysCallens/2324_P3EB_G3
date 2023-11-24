const editor = CKEDITOR.ClassicEditor.create(document.querySelector("#editor"), {
  toolbar: {
      items: [
          'undo', 'redo',
          '|', 'heading',
          '|', 'bold', 'italic',
          '|', 'link', 'uploadImage', 'insertTable', 'mediaEmbed',
          '|', 'bulletedList', 'numberedList', 'outdent', 'indent',
          '|', 'style',
      ]
  },
  style: {
      definitions: [
        {
          name: 'Article category',
          element: 'h3',
          classes: [ 'category' ]
        },
        {
          name: 'Info box',
          element: 'p',
          classes: [ 'info-box' ]
        },
      ]
  },
});