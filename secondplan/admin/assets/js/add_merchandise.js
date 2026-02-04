
const imageInput = document.getElementById('image');
const previewImg = document.getElementById('preview-img');
const imagePreview = document.getElementById('image-preview');
const stockInput = document.getElementById('stock');
const stockIndicator = document.getElementById('stock-indicator');
const stockStatus = document.getElementById('stock-status');

function removeImage() {
  imageInput.value = '';
  imagePreview.classList.remove('show');
}

imageInput.addEventListener('change', e => {
  const file = e.target.files[0];
  if(!file) return removeImage();
  previewImg.src = URL.createObjectURL(file);
  imagePreview.classList.add('show');
});

stockInput.addEventListener('input', () => {
  const val = parseInt(stockInput.value) || 0;
  if(val === 0){
    stockIndicator.className = 'stock-indicator low show';
    stockStatus.textContent = 'Out of Stock';
  } else if(val < 5){
    stockIndicator.className = 'stock-indicator low show';
    stockStatus.textContent = 'Low Stock';
  } else {
    stockIndicator.className = 'stock-indicator good show';
    stockStatus.textContent = 'Stock Good';
  }
});

document.getElementById('merchForm').addEventListener('submit', e=>{
  e.preventDefault();
  alert('Merchandise added (UI only)');
});
