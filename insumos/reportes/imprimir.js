const urlParametros = new URLSearchParams(window.location.search);
const pantallaNombre = urlParametros.get('pantalla');
const ultimoId = urlParametros.get('ultimoId');
let url = '';
//if(pantallaNombre == 'compras') url = './../php/compra.php' + '?ultimoId=' + ultimoId;
url = './../php/' + pantallaNombre + '?ultimoId=' + ultimoId;
console.log(pantallaNombre + '/' + ultimoId)
const elementDom = document.querySelector('body');
fetch(url)
  .then(response => response.json()) // Convertir la respuesta a JSON
  .then(data => {
    // Hacer algo con los datos
    console.log(data);
    const obj = data[0];
    for (let clave in obj) {
      if (obj.hasOwnProperty(clave)) {
        let key = clave;
        let arr = clave.split(" ");
        if(arr.length == 2 && arr[0] == 'Cantidad') key = arr[0];
        if(arr.length == 4 && parseInt(arr[3]) != isNaN) key = arr[0] + ' ' + arr[1] + ' ' + arr[2];
        const literal = `<div class="contenedor-imprimir" style="margin:1%; padding:0.3%">
        <label for="">${key}: </label>
        <span>${obj[clave]}</span>
        </div>`
        elementDom.innerHTML += literal;
      }
    }
    //elementDom.innerHTML += '<a class="btn btn-primary" href="./../insumos">Volver</a>'
    console.log(elementDom)
    const options = prepararOptions();
    //html2pdf().set(options).from(elementDom).save();
    //html2pdf().from(elementDom).save();
    html2pdf().set(options).from(elementDom).toPdf().get('pdf').then( pdf => {
      elementDom.innerHTML += '<a class="btn btn-primary" href="./../">Volver</a>'
      window.open(pdf.output('bloburl'), '_blank');
      //pdf.save();
    });
  })

/*const reporte = localStorage.getItem('reporte');
elementDom.innerHTML = reporte;
const options = prepararOptions();*/
//html2pdf().set(options).from(elementDom).save();
//html2pdf().from(elementDom).save();
/*html2pdf().set(options).from(elementDom).toPdf().get('pdf').then( pdf => {
  window.open(pdf.output('bloburl'), '_blank');
  //pdf.save();
})*/

function prepararOptions() {
  return {
    filename: 'compras.pdf',
    margin: 0.5,
    image: {type:'jpeg', quality:0.98, scrollX: 0, scrollY: 0},
    html2canvas: {scale:2},
    jsPDF: {
      unit:'in',
      format:'a4',
      orientation:'portrait'// horizontal:'landscape'
    }
  }
}
