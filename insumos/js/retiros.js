const selectFactSel = document.getElementById('ABMnFactSel-ret');
const selectProductos = document.getElementById('AMBproducto-ret');
const selectProveedores = document.getElementById('AMBproveedor');
const botonAgregarRet = document.getElementById('btnCargar-ret');
listProd = [];
//limpiarLosAgregar(selectProveedores);

let facturas;
selectProveedores.addEventListener('change', async (e) => {
  const id_proveedor = e.target.value;
  const url = './php/retiro.php?id_prov=' + id_proveedor;
  const response = await fetch(url);
  const data = await response.json();
  console.log(data);
  facturas = data;
  selectFactSel.innerHTML = ''; //reinicio el select
  selectFactSel.innerHTML  += '<option value="0">Selecciona una factura</option';
  facturas.forEach(factura => {
    let value;
    let text;
    let i = 0
    for (var clave in factura) {
      if (factura.hasOwnProperty(clave)) {
        if(i == 0) value = factura[clave];
        else text = factura[clave];
      }
      i++;
    }
    const literal = `<option value="${value}">${text}</option`;
    selectFactSel.innerHTML += literal;
  });
});

selectFactSel.addEventListener('change', async (e) => {
  const id_factura = e.target.value;
  const url = './php/retiro.php?id_compra=' + id_factura;
  const response = await fetch(url);
  const data = await response.json();
  productos = data;
  selectProductos.innerHTML = ''; //reinicio el select
  selectProductos.innerHTML  += '<option value="0">Selecciona un producto</option';
  productos.forEach(producto => {
    const value = producto.producto;
    const text = producto.descripcion;

    const literal = `<option value="${value}">${text}</option`;
    selectProductos.innerHTML += literal;
  });
});

selectProductos.addEventListener('change', (e) => {
  const id_producto = e.target.value;
  const producto = productos.find(prod => prod.producto == id_producto);
  const inputCant = document.getElementById('ABMcant');
  inputCant.value = producto.cantidad;
});

function limpiarLosAgregar(selectProveedores) {
  let index = -1;
  console.log(selectProveedores)
  index = selectProveedores.options.length - 1;
  console.log(index)
  selectProveedores.remove(index);
}
