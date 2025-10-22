document.getElementById('generateBarcode').addEventListener('click', function() {
    fetch('../src/generate_barcode.php')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById('barcodeField').value = data.barcode;
        } else {
          alert('خطأ في توليد الباركود: ' + data.error);
        }
      })
      .catch(err => alert('فشل الاتصال بالسيرفر: ' + err));
});