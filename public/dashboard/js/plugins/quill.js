"use strict";
// quill
if (jQuery("#editor").length) {
  if (typeof Quill !== "undefined" && !document.querySelector("#editor").__quill) {
    const quill = new Quill("#editor", {
      theme: "snow"
    });
  }
}

if (jQuery("#editor1").length) {
  if (typeof Quill !== "undefined" && !document.querySelector("#editor1").__quill) {
    const quill = new Quill('#editor1', {
      theme: 'snow'
    });
    quill.setContents([
      { insert: "Add your content here." },
    ]);
  }
}