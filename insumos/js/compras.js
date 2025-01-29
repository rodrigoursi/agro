const inputCant = document.getElementById('ABMcant');
const inputPrecio = document.getElementById('ABMprecio');
let inputTotal = document.getElementById('ABMtotal');

inputCant.addEventListener('input', () => {
  if(inputPrecio.value == "") return;
    const precio = parseFloat(inputPrecio.value);
    const cant = parseFloat(inputCant.value);
    const total = precio * cant;
    inputTotal.value = total;
});

inputPrecio.addEventListener('input', () => {
  if(inputCant.value == "") return;
    const precio = parseFloat(inputPrecio.value);
    const cant = parseFloat(inputCant.value);
    const total = precio * cant;
    inputTotal.value = total;
});
