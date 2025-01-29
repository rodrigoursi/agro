class Login {
  constructor() {
    this.token = localStorage.getItem('tk');
  }

  async iniciarSesion() {
    let respuesta = false;
    const inputUser = document.getElementById('userLogin');
    const inputPass = document.getElementById('passLogin');
    const oLogin = {user: inputUser.value, pass: inputPass.value};
    try {
      let response = await fetch('./php/login.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(oLogin)
      });
      const data = await response.json();
      if(data[0].estado == 'error') {
        alert(data[0].mensaje);
        return respuesta;
      }
      console.log(data[0].mensaje);
      localStorage.setItem('usuario', data[0].mensaje);
      localStorage.setItem('tk', data[0].token);
      respuesta = true;
      this.token = data[0].token;

    } catch (err) {
      console.error('Error', err);
    }
    return respuesta;
  }

  validarSesion() {
    const local = localStorage.getItem('tk');
    if(local != null) {
      this.token = local;
      return true;
    }
    this.token = null;
    return false;
  }

  cerrarSession() {
    localStorage.removeItem('tk');
    localStorage.removeItem('usuario');
    window.location.reload();
  }

  validarToken(token) {
    if(token.tk_valido == false) {
      swal(token.motivo).then(() => {
        this.cerrarSession();
      });
    }
  }

  async devolverRol() {
    const token = this.token;
    try {
      const response = await fetch('/agro/php/login.php?token=' + token);
      const data = await response.json();
      console.log(data)
      if(data.length < 1) {
        swal('No se encontro rol');
        this.cerrarSession();
        return;
      }
      return data;
    } catch (e) {
      swal('Error interno', e.message, 'error').then(() => this.cerrarSession());
      return;
    }
  }
}
