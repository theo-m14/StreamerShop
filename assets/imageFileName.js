document.addEventListener('turbo:load', () => {
const fileInput = document.querySelector("#product_imageFile_file");
 if (fileInput) {
  fileInput.onchange = () => {
    if (fileInput.files.length > 0) {
      const fileName = document.querySelector("#product_imageFile_name");
      fileName.textContent = fileInput.files[0].name;
    }
  };
 }
});