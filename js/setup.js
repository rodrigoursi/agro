const OiniciarSesion = new Login();
const respSesion = OiniciarSesion.validarSesion();
console.log(respSesion);
if(!respSesion) location.href = '/agro/'; // aca debo poner "/" cuando estemos en produccion
habModulosNav();
eventos();

function habModulosNav() {
  const oLis = document.querySelectorAll('.sesionIni');
  oLis.forEach(li => {
    li.style.display = 'list-item';
  });
}

function cerrarSesion() {
  OiniciarSesion.cerrarSession();
}

async function eventos() {
  const botones = document.querySelectorAll('#panelOpciones button');
  let adm = '';
  if(OiniciarSesion.token != null) {
    const oRol = await OiniciarSesion.devolverRol();
    console.log(oRol.data[0].nombre)
    if(oRol.data.length > 0) {
      if(oRol.data[0].nombre.toUpperCase() == 'ADMIN') adm = 'adm/';
    }
  }
  botones.forEach(boton => {
    const url = './' + adm + boton.id + '.html';
    boton.addEventListener('click', () => {
      location.href = url;
    });
  });

  /*const btnCompras = document.getElementById('compras');
  btnCompras.addEventListener('click', () => {
    location.href = './compras.html';
  });*/
}
