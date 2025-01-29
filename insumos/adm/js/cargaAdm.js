const OiniciarSesion = new Login();
const respSesion = OiniciarSesion.validarSesion();
if(!respSesion) location.href = '/agro/';
OiniciarSesion.devolverRol().then(rol => {
  OiniciarSesion.validarToken(rol.tk);
  if(rol.data.length > 0) {
    if(rol.data[0].nombre.toUpperCase() != 'ADMIN') location.href = './../';
  }
});

const domContTabla = document.getElementById('contenedor-tabla');
cargarTabla();
eventos();

function cerrarSesion() {
  OiniciarSesion.cerrarSession();
}

async function cargarTabla() {
  const url = './../php/' + domContTabla.getAttribute('name') + '.php?ultimoId=0';
  const response = await fetch(url);
  const data = await response.json();
  console.log(data);
  const domTabla = crearTabla('tabla-compras', data, 'simple', true);
   domContTabla.prepend(domTabla);
}

function eventos() {
  const oBtn = document.getElementById('detalle-tabla');
  oBtn.addEventListener('click', () => {
    let php = domContTabla.getAttribute('name') + '.php'
    const filaCapturada = capturarFilas('tabla-compras');
    console.log(filaCapturada);
    location.href ='./../reportes/rpt_ABM.html?pantalla=' + php + '&ultimoId=' + filaCapturada[0].Id;
  });
}
