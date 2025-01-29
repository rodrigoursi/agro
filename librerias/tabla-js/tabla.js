/*
* con esta funcion hago la creacion de tabla.
* @PARAMETROS: string idTabla(es el id que le quiero poner a la etiqueta table)
* @PARAMETROS: json data(es un json de datos)
* @PARAMETROS: string tipoSelecc(es el tipo de forma de seleccionar filas que tendra la tabla, solo admite 'simple', 'multi', o vacio)
* @PARAMETROS: bool buscador(si es true significa que la tabla se creara con buscador)
* @return: HTML TABLE tabla(devuelve un dom html de tipo tabla ya lista para realizar un append en algun html contenedor)
*/
function crearTabla(idTabla, data, tipoSelecc, buscador) {
  const tabla = document.createElement('table');
  tabla.id = idTabla;
  tabla.className = 'table';
  tabla.setAttribute('data-tipoSelec', tipoSelecc);
  const arrCabecera = armarArrCabecera(data);
  tabla.innerHTML = '<thead><tr class="cabecera" style="text-align:center"></tr></thead><tbody></tbody>';
  const cabecera = tabla.querySelector('.cabecera');
  arrCabecera.forEach(cab => {
    const th = document.createElement('th');
    th.setAttribute('scope', 'col');
    th.setAttribute('name', cab);
    th.textContent = cab;
    cabecera.append(th);
  });
    //---------------------//
    const tBody = tabla.querySelector('tbody');
    let color = true;
    data.forEach(pedido => {
      const tr = document.createElement('tr');
      if(color) {
        tr.className = 'table-light';
        tr.dataset.color = 'light';
        color = false;
      } else color = true;
      for(columna in pedido) {
        tr.innerHTML += `<td style="text-align:center" data-cellSelec="false" name=${columna}>${pedido[columna]}</td>`;
      }
      tr.addEventListener('click', e => {console.log("pasa aca tambien")
        if(tipoSelecc == 'multi') seleccionarFilasMulti(e);
        if(tipoSelecc == 'simple') seleccionarFila(e, tabla);
        //if(tipoSelecc == '') seleccionarFila(e);
      });
      tBody.append(tr);
    });
    if(buscador) return crearBuscador(tabla, data);
  //console.log(tabla)
  return tabla;
}

/*
* funcion interna, con esta funcion armo el array de cabecera.
* @return: devuelvo un objeto de cabeceras.
*/
function armarArrCabecera(data) {
  const aCab = Object.keys(data[0]);
  return aCab;
}

/*
* con esta funcion oculto cabeceras y datos de esa misma cabeceras que no quiero mostrar.
* @PARAMETROS: HTML TABLE tabla(es el objeto html tabla)
* @PARAMETROS: string name(es el nombre de la columna que toma la funcion de referencia para esconder justamente dicha columna con sus datos)
* @return: VOID
*/
function ocultarCabecera(tabla, name) {
  const columnas = tabla.querySelectorAll(`[name="${name}"]`);
  if(tabla.hasAttribute('data-colocu')) {
    let nuevoSet = tabla.dataset.colocu + "," + name;
    tabla.dataset.colocu = nuevoSet;
  } else tabla.dataset.colocu = name;
  columnas.forEach(columna => {
    columna.style.display = 'none';
  });
  const option = tabla.querySelector(`form select option[value = '${name}']`);
  console.log(tabla.querySelector('form select option'))
  console.log(option)
  if(option) option.remove();
}

/*
* con esta funcion cambio las cabeceras.
* @PARAMETROS: HTML TABLE tabla(es el objeto html tabla)
* @PARAMETROS: string cabVieja(es el nombre de la columna que deseas reemplazar)
* @PARAMETROS: string cabNueva(es el nuevo nombre de la columna que estas reemplazando)
* @return: VOID
*/
function cambiarCabecera(tabla, cabVieja, cabNueva) {
  const cabecera = tabla.querySelector(`.cabecera [name="${cabVieja}"]`);
  cabecera.textContent = cabNueva;
}

/*
* funcion interna, con esta funcion se usa en capturarFila.
* @return: VOID.
*/
function seleccionarFila(e, tabla) {
  const seleccionado = tabla.querySelector('tbody .table-primary');
  console.log(seleccionado);
  if(seleccionado) {
    seleccionado.classList.remove('table-primary');
    seleccionado.removeAttribute('data-selec');
    if(seleccionado.dataset.color != undefined) {
      const claseColor = 'table-' + seleccionado.dataset.color;
       seleccionado.classList.add(claseColor);
    }
  }
  e.currentTarget.classList.remove('table-light');
  e.currentTarget.classList.toggle('table-primary');
  e.currentTarget.dataset.selec = "true";
}

/*
* funcion interna, con esta funcion se usa en capturarFila.
* @return: VOID.
*/
function seleccionarFilasMulti(e) {
  e.currentTarget.classList.toggle('table-primary');
  if(e.currentTarget.dataset.selec == "true") {
    e.currentTarget.dataset.selec = "false";
    if(e.currentTarget.dataset.color != undefined) {
      const claseColor = 'table-' + e.currentTarget.dataset.color;
       e.currentTarget.classList.add(claseColor);
    }
    return;
  }
  e.currentTarget.classList.remove('table-light');
  e.currentTarget.dataset.selec = "true";
}

/*
* con esta funcion capturo las filas seleccionadas de la tabla.
* @PARAMETROS: string idTabla(es el id de la tabla)
* @return: arrayobject arrObjFila(es un array de objetos con los filas seleccionadas)
*/
function capturarFilas(idTabla) {
  const tabla = document.getElementById(idTabla);
  let filas = tabla.querySelectorAll('tbody [data-selec="true"]');
  filas = [...filas]; // paso a array para poder usar el metodo map, porq el nodolist solo soporta foreach
  const arrObjFila = filas.map(fila => {
    let obj = {};
    const columnas = [...fila.children]; // paso a array para poder usar el metodo map, porq el nodolist solo soporta foreach
    columnas.forEach(columna => {
      obj[columna.getAttribute("name")] = columna.textContent;
    });
    return obj;
  })
  console.log(arrObjFila);
  return arrObjFila;
}

/*
* funcion interna, esta funcion se usa en crearTabla.
* @return: HTML DIV contenedor(devuelve el contenedor q contiene el buscador y la tabla).
*/
function crearBuscador(tabla, data) {
  const contenedor = document.createElement("div");
  const form = document.createElement("form");
  const input = document.createElement("input");
  input.className = "form-control mx-2";
  form.className = "form-buscador"
  const select = crearSelect(data[0]);
  form.append(select);
  form.append(input);
  contenedor.append(form);
  contenedor.append(tabla);
  input.addEventListener("input", (e)=> {
    let clave = contenedor.querySelector(`select`).value;
    let captura = e.target.value;
    const nuevaData = buscar(clave, captura, data);

    //const contenedorPadre = tabla.parentNode;
    //document.getElementById(tabla.id).remove();
    //contenedorPadre.append(crearTabla(tabla.id, buscar(captura, data), "simple", true));
    /////////////////////////////////////////////////////////////////////////
    const tBody = tabla.querySelector('tbody');
    const tipoSelecc = tabla.dataset.tiposelec;
    tBody.innerHTML = '';
    let color = true;
    nuevaData.forEach(d => {
      const tr = document.createElement('tr');
      if(color) {
        tr.className = 'table-light';
        tr.dataset.color = 'light';
        color = false;
      } else color = true;
      for(columna in d) {
        tr.innerHTML += `<td style="text-align:center" data-cellSelec="false" name=${columna}>${d[columna]}</td>`;
      }
      tr.addEventListener('click', e => {
        if(tipoSelecc == 'multi') seleccionarFilasMulti(e);
        if(tipoSelecc == 'simple') seleccionarFila(e, tabla);
        //if(tipoSelecc == '') seleccionarFila(e);
      })
      tBody.append(tr);
    });
    //if(!contenedorPadre.hasAttribute("data-colocu")) return;
    if(!contenedor.hasAttribute("data-colocu")) return;
    //let datasetOculto = contenedorPadre.dataset.colocu;
    let datasetOculto = contenedor.dataset.colocu;
    datasetOculto = datasetOculto.split(",");
    console.log(datasetOculto)
    datasetOculto.forEach(dataset => {
      //ocultarCabecera(contenedorPadre, dataset);
      ocultarCabecera(contenedor, dataset);
    });
  });
  return contenedor;
}

/*
* funcion interna, esta funcion se usa en crearBuscador.
* @return: HTML SELECT select(devuelve el select q acompaña al input buscador).
*/
function crearSelect(data) {
  const select = document.createElement("select");
  select.className = "form-selec mx-2";
  for (let clave in data) {
    /*const option = document.createElement("option");
    option.value = clave
    option.textContent = clave;*/
    select.innerHTML += `<option value="${clave}">${clave}</option>`;
    //select.append(option);
  }
  return select;
}


function buscar(clave, valor, data) {
  return data.filter(d => d[clave].includes(valor));
}
