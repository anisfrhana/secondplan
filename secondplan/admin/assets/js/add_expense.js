
  const form = document.getElementById('expenseForm');
  const amount = document.getElementById('amount');
  const totalDisplay = document.getElementById('total-display');
  const totalValue = document.getElementById('total-value');
  const receiptInput = document.getElementById('receipt');
  const uploadPreview = document.getElementById('upload-preview');
  const previewImg = document.getElementById('preview-img');
  const fileName = document.getElementById('file-name');
  const fileSize = document.getElementById('file-size');

  function removeFile() {
    receiptInput.value = '';
    uploadPreview.classList.remove('show');
  }

  receiptInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return removeFile();
    fileName.textContent = file.name;
    fileSize.textContent = (file.size/1024).toFixed(1) + " KB";
    if (file.type.startsWith('image/')) previewImg.src = URL.createObjectURL(file);
    else previewImg.src = '';
    uploadPreview.classList.add('show');
  });

  amount.addEventListener('input', () => {
    if(amount.value) {
      totalValue.textContent = 'RM ' + parseFloat(amount.value).toFixed(2);
      totalDisplay.style.display = 'block';
    } else {
      totalDisplay.style.display = 'none';
    }
  });

  form.addEventListener('submit', e => {
    e.preventDefault();
    alert('Expense saved (UI only)');
  });
