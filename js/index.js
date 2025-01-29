const OiniciarSesion = new Login();
let local = localStorage.getItem('usuario');
if(local != null) crearPanel();
else acceder()

function crearPanel() {
  habModulosNav();
  const oLi = document.getElementById('btnCerrarSesion');
  oLi.style.display = 'list-item';
  const contenedor = document.getElementById('contenedorPrincipal');
  const contenedorLogin = document.getElementById('contenedorLogin');
  contenedor.classList.replace('contenedorPrincipal-login', 'contenedorPrincipal-panel');
  console.log(local)
  contenedor.innerHTML = `<h1 class="mt-5">Bienvenido usuario <span style="font-weight:bold;">${local.toUpperCase()}</span></h1>
    <div class="contenedorPanel" id="contenedorPanel">
    <h1 style="text-align: center;" class="mt-3">Panel</h1>
    <div class="panel-opciones container my-4" id="panelOpciones">
    <button type="button" class="btn btn-primary" onclick="location.href='./insumos'">INSUMOS</button>
    <button type="button" class="btn btn-primary">FUMIGACION</button>
    <button type="button" class="btn btn-primary">SIEMBRA</button>
    <button type="button" class="btn btn-primary">COSECHA</button>
    <button type="button" class="btn btn-primary">LOGISTICA</button>
    <button type="button" class="btn btn-primary">STOCK</button>
    </div>
    </div>`;

}

function habModulosNav() {
  const oLis = document.querySelectorAll('.sesionIni');
  oLis.forEach(li => {
    li.style.display = 'list-item';
  });

}

function acceder() {
  const botonAcceder = document.getElementById('accederLogin');
  botonAcceder.addEventListener('click', async(e) => {
    e.preventDefault();
    const respSesion = await OiniciarSesion.iniciarSesion();

    if(respSesion) {
      local = localStorage.getItem('usuario');
      crearPanel();
    }
  });
}

function cerrarSesion() {
  OiniciarSesion.cerrarSession();
}
