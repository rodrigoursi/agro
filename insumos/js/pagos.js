const selectProveedores = document.getElementById('AMBproveedor');
const selectFactSel = document.getElementById('ABMnFactSel-pagos');
const selectMediosPagos = document.getElementById('AMBmedioPago');
const divCheques = document.getElementById('cont-cheque-form');
const inputImporte = document.getElementById('ABMimporte');
let importePago;


let facturas;
selectProveedores.addEventListener('change', async (e) => {
  const id_proveedor = e.target.value;
  const url = './php/pago.php?id_prov=' + id_proveedor;
  const response = await fetch(url);
  const data = await response.json();
  console.log(data);
  facturas = data;
  selectFactSel.innerHTML = ''; //reinicio el select
  selectFactSel.innerHTML  += '<option value="0">Selecciona una factura</option';
  facturas.forEach(factura => {
    let value = factura.id
    let text = factura.factura;
    const literal = `<option value="${value}">${text}</option`;
    selectFactSel.innerHTML += literal;
  });
});

selectMediosPagos.addEventListener('change', (e) => {
  if(e.target.selectedOptions[0].text == 'CHEQUE') {
    divCheques.style.display = 'block';
    inputImporte.value = '0';
    inputImporte.disabled = true;
  }
  if(e.target.selectedOptions[0].text != 'CHEQUE') {
    divCheques.style.display = 'none';
    inputImporte.disabled = false;
  }
});

selectFactSel.addEventListener('change', (e) => {
  const value = e.target.value;
  const factura = facturas.find(fact => fact.id = value);
  inputImporte.value = factura.saldo;
});
