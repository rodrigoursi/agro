let listProd = [];
let listaCheque = [];
const OiniciarSesion = new Login();
const respSesion = OiniciarSesion.validarSesion();
console.log(respSesion);
if(!respSesion) location.href = '/agro/'; // aca debo poner "/" cuando estemos en produccion
habModulosNav();
fechaActual();
cargarSelectores().then(data => {
  eventos(data);
})
/*const lotes = cargarSelectores();
console.log(lotes);*/


function fechaActual() {
  const fecha = document.getElementById('ABMfecha');
  const fechaActual = new Date();
  const dia = fechaActual.getDate().toString().padStart(2, '0');
  const mes = (fechaActual.getMonth() + 1).toString().padStart(2, '0');
  const anio = fechaActual.getFullYear();
  fecha.value = dia + '/' + mes + '/' + anio;
}

function habModulosNav() {
  const oLis = document.querySelectorAll('.sesionIni');
  oLis.forEach(li => {
    li.style.display = 'list-item';
  });
}

function eventos(data) {
  const selects = document.querySelectorAll('#formABM .form-select');
  selects.forEach(select => {
    select.addEventListener('change', (e) => {
      if(e.target.id == 'AMBdestino' && e.target.value != "agregar") {
        const destino = e.target.value;
        const lotes = data.filter(lote => lote.destino == destino);
        habilitarLote();
        selectLote = document.getElementById('AMBlote');
        agregarLotes(selectLote, lotes, 'Lote');
      }
      if(e.target.value == "agregar"){
        swal({content:'input'}).then(value => {
          const select = e.target;
          const ultimoIndex = select.options.length - 1;
          select.options[ultimoIndex].value = 'nuevo';
          select.options[ultimoIndex].text = value.toUpperCase();
        });
      }
    });
  });

  const formulario = document.getElementById('formABM');

  //EVENTO PARA AGREGAR PRODUCTO
  const agregar = formulario.querySelector('#agregar');
  const select = document.getElementById('AMBproducto') ? document.getElementById('AMBproducto') : document.getElementById('AMBproducto-ret');
  agregar.addEventListener('click', () => {
    const ul = formulario.querySelector('.listProd');
    if(formulario.name == 'form-pago') {
      const selectMP = document.getElementById('AMBmedioPago');
      const numCheq = document.getElementById('ABMnumCheq');
      const fechaPago = document.getElementById('ABMfecPago');
      const importeCheq = document.getElementById('ABMimporteCheq');
      if(!numCheq.value || !fechaPago.value || !importeCheq.value) return;
      agregarCheque(numCheq.value, fechaPago.value, importeCheq.value, ul);
      selectMP.disabled = true;
      numCheq.value = '';
      fechaPago.value = '';
      importeCheq.value = '';
      console.log(listaCheque)
      return;
    }
    if(select.value == 0) return;
    const idProd = select.value;
    const inputCant = document.querySelector('.cont-selecProd #ABMcant') ? document.querySelector('.cont-selecProd #ABMcant') : formulario.querySelector('#ABMcant');
    const inputPr = document.querySelector('.cont-selecProd #ABMprecio');
    const inputTot = formulario.querySelector('#ABMtotal');
    if(formulario.name == 'form-compra') if(!inputPr.value) return;
    const cant = inputCant.value;
    const textProd = select.selectedOptions[0].textContent;
    let precio;
    let total;
    if(formulario.name == 'form-compra') {
      precio = inputPr.value;
      total = parseFloat(cant) * parseFloat(precio);
    }

    const objProducto = {id_producto:idProd, producto:textProd, cantidad:parseFloat(cant), precio:parseFloat(precio), total:total};

    if(formulario.name == 'form-retiro') agregarProductoSinPrecio(objProducto, ul);
    else inputTot.value = agregarProducto(objProducto, ul, parseFloat(inputTot.value));

    inputCant.value = 1;
    console.log(listProd);
  });

  //EVENTO ENVIAR FORMULARIO
  formulario.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = recupDatosForm();
    console.log(data);
    enviarForm(data);
    //generarPdf(e.target);
  });

}

function agregarProducto(objProducto, ul, total) {
  ul.classList.remove('oculto');
  let productoExiste = listProd.find(p => p.id_producto === objProducto.id_producto);
  if(productoExiste) {
    total -= productoExiste.total;
    productoExiste.cantidad += objProducto.cantidad;
    productoExiste.precio = objProducto.precio;
    productoExiste.total = productoExiste.cantidad * productoExiste.precio;
    total += productoExiste.total;
    const li = ul.querySelector(`li[data-idProd="${objProducto.id_producto}"]`);
    li.innerHTML = `<div><span>${productoExiste.producto}</span><span>${productoExiste.cantidad}</span><span>$${productoExiste.precio}</span><span>$${productoExiste.total}</span></div>`;
  } else {
    listProd.push(objProducto);
    total += objProducto.total;
    //total += isNaN(objProducto.total) ? 0 : objProducto.total;
    ul.innerHTML += `<li data-idProd='${objProducto.id_producto}'><div><span>${objProducto.producto}</span><span>${objProducto.cantidad}</span><span>$${objProducto.precio}</span><span>$${objProducto.total}</span></div></li>`;
  }
  console.log(total);
  return total;
}

function agregarProductoSinPrecio(objProducto, ul) {
  ul.classList.remove('oculto');
  let productoExiste = listProd.find(p => p.id_producto === objProducto.id_producto);
  if(productoExiste) {
    productoExiste.cantidad += objProducto.cantidad;
    const li = ul.querySelector(`li[data-idProd="${objProducto.id_producto}"]`);
    li.innerHTML = `<div><span>${productoExiste.producto}</span><span>${productoExiste.cantidad}</span></div>`;
  } else {
    listProd.push(objProducto);
    ul.innerHTML += `<li data-idProd='${objProducto.id_producto}'><div><span>${objProducto.producto}</span><span>${objProducto.cantidad}</span></div></li>`;
  }
}

function agregarCheque(numCheq, fechaPago, importeCheq, ul) {
  const existe = listaCheque.some(cheq => cheq.numCheq === numCheq);
  if(existe) {
    swal("ERROR..!", "Numero de cheque ya ingresado previamente", "error");
    return;
  }
  listaCheque.push({numCheq:numCheq, fechaPago:fechaPago, importeCheq:importeCheq});
  const inputImporte = document.getElementById('ABMimporte');
  const importe = parseFloat(inputImporte.value) + parseFloat(importeCheq);
  inputImporte.value = importe;
  ul.classList.remove('oculto');
  ul.innerHTML += `<li><div><span>${numCheq}</span><span>${fechaPago}</span><span>${importeCheq}</span></div></li>`;
}

async function cargarSelectores() {
  const selectProveedores = document.getElementById('AMBproveedor');
  const selectProductos = document.getElementById('AMBproducto');
  const selectCondPago = document.getElementById('AMBcondPago');
  const selectDestino = document.getElementById('AMBdestino');
  const selectLote = document.getElementById('AMBlote');
  const selectFactSel = document.getElementById('ABMnFactSel');
  const selectMoneda = document.getElementById('AMBmoneda');
  const selectMediosPagos = document.getElementById('AMBmedioPago');
  const oDatosparaSelectores = {proveedores:true, productos:true, condiciones:true, destinos:true, lotes:true, facturas:true, monedas:true, mpago:true, tk:localStorage.getItem('tk')};
  if(selectProveedores == null) oDatosparaSelectores.proveedores = false;
  if(selectProductos == null) oDatosparaSelectores.productos = false;
  if(selectCondPago == null) oDatosparaSelectores.condiciones = false;
  if(selectDestino == null) oDatosparaSelectores.destinos = false;
  if(selectLote == null) oDatosparaSelectores.lotes = false;
  if(selectFactSel == null) oDatosparaSelectores.facturas = false;
  if(selectMoneda == null) oDatosparaSelectores.monedas = false;
  if(selectMediosPagos == null) oDatosparaSelectores.mpago = false;
  console.log(oDatosparaSelectores);
  const response = await fetch('./php/carga.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(oDatosparaSelectores)
  });
  const data = await response.json();
  console.log(data);
  OiniciarSesion.validarToken(data.tk);
  for (const clave in data) {
    if (data.hasOwnProperty(clave)) {
      switch (clave) {
        case 'PROVEEDORES':
          agregarOption(selectProveedores, data[clave], 'Proveedor');
          break;
        case 'PRODUCTOS':
          agregarOption(selectProductos, data[clave], 'Producto');
          break;
        case 'CONDICIONES':
          agregarOption(selectCondPago, data[clave], 'Condición de Pago');
          break;
        case 'DESTINOS':
          agregarOption(selectDestino, data[clave], 'Destino');
          break;
        case 'MONEDAS':
          agregarOption(selectMoneda, data[clave], 'Moneda');
          break;
        case 'FACTURAS':
          agregarOption(selectFactSel, data[clave], 'Factura');
          break;
        case 'MPAGO':
          agregarOption(selectMediosPagos, data[clave], 'Medio de Pago');
          break;
        default:

      }
    }
  }
  return data['LOTES'];
}

function agregarOption(domSelect, arrObj, agregar) {
  arrObj.forEach(item => {
    let value;
    let text;
    let i = 0
    for (var clave in item) {
      if (item.hasOwnProperty(clave)) {
        if(i == 0) value = item[clave];
        else text = item[clave];
      }
      i++;
    }
    const literal = `<option value="${value}">${text}</option`;
    domSelect.innerHTML += literal;
  });
  const nombreForm = document.getElementById('formABM').name;
  const arrNomForm = nombreForm.split('-');
  if(arrNomForm[1] == 'compra' || domSelect.name == 'destino' || domSelect.name == 'mpago') {
    const literal = `<option value="agregar">AGREGAR ${agregar}</option>`
    domSelect.innerHTML += literal;
  }
}

function habilitarLote() {
  const selectLote = document.getElementById('AMBlote');
  selectLote.disabled = false;
}

function agregarLotes(selectLote, lotes, stringLote) {
  //selectLote.remove(1);
  selectLote.innerHTML = `<option value="0" selected>Selecciona un lote</option>`;
  lotes.forEach(lote => {
    const literal = `<option value="${lote.id}">${lote.nombre}</option`;
    selectLote.innerHTML += literal;
  });
  const literal = `<option value="agregar">AGREGAR ${stringLote}</option>`
  selectLote.innerHTML += literal;
}

async function enviarForm(data) {
  data['tk'] = localStorage.getItem('tk');
  form = document.getElementById('formABM');
  const nombre = form.name.split('-')[1];
  const php = nombre + '.php';
  console.log(data)
  console.log('./php/' + php)
  const response = await fetch('./php/' + php, {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  });
  const res = await response.json();
  console.log(res)
  OiniciarSesion.validarToken(res.tk);
  if(res.tk.tk_valido) {
    const title = res.status == 'success' ? 'Exito!' : 'Error!';
    const text = res.result.mensaje;
    swal(title, text, res.status).then(() => {
      //const form = document.getElementById('formABM');
      console.log(form.name);
      //const nombre = form.name.split('-')[1];

      //if(res.status == 'success') location.href ='./reportes/rpt_ABM.html?pantalla=' + nombre + '&ultimoId=' + res.result.ultimoId;
      if(res.status == 'success') location.href ='./reportes/rpt_ABM.html?pantalla=' + php + '&ultimoId=' + res.result.ultimoId;
    });
  }
}

function cerrarSesion() {
  OiniciarSesion.cerrarSession();
}

function recupDatosForm() {
  const data = {};
  const selects = document.querySelectorAll('#formABM .form-select');
  const inputs = document.querySelectorAll('#formABM input');
  inputs.forEach(input => {
    if(input.name == 'cant' || input.name == 'precio') return; // esto por la cpantalla de compras
    if(input.name == 'nCheq' || input.name == 'fechaPago' || input.name == 'importe-cheque') return; // esto por la pantalla pagos
    const clave = input.name;
    data[clave] = input.value;
  });
  selects.forEach(select => {
    if(select.name == 'producto') return;
    let clave = select.name;
    data[clave] = select.selectedOptions[0].textContent;
    clave = 'id_' + select.name;
    data[clave] = select.value;
  });
  data['productos'] = listProd;
  data['cheques'] = listaCheque;
  return data;
}

function generarPdf(elementDom) {
  const reporte = `<div class="contenedor-imprimir" style="margin:2%">
    <label for="">Fecha:</label>
    <span>24/09/2024</span>
  </div>
  <div class="contenedor-imprimir" style="margin:2%">
    <label for="">Proveedor:</label>
    <span>Proveedor 1</span>
  </div>
  <div class="contenedor-imprimir" style="margin:2%">
    <label for="">Nº Factura:</label>
    <span>24092024</span>
  </div>
  <div class="contenedor-imprimir" style="margin:2%">
    <label for="">Producto:</label>
    <span>Producto 1</span>
  </div>
  <div class="contenedor-imprimir" style="margin:2%">
    <label for="">Cantidad:</label>
    <span>2</span>
  </div>
  <div class="contenedor-imprimir" style="margin:2%">
    <label for="">Precio unitario:</label>
    <span>200</span>
  </div>
  <div class="contenedor-imprimir" style="margin:2%">
    <label for="">Precio total:</label>
    <span>400</span>
  </div>
  <div class="contenedor-imprimir" style="margin:2%">
    <label for="">Condicion:</label>
    <span>Contado</span>
  </div>`;
  localStorage.setItem('reporte', reporte);
  location.href = './reportes/rpt_ABM.html';
  //html2pdf().from(elementDom).save();

}
