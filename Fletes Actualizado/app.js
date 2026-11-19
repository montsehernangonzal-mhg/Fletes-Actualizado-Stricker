function contexto(){
	return {
		// Colecciones
		Choferes:[],
		Clientes:[],
		Fletes:[],
		Unidades:[],

		// muestra de formularios
		formChofer:{},
		formCliente:{},
		formFlete:{},
		formUnidad:{},

		init(){
			//aqui consumimos el api y llenamos las colecciones
			fetch('api.php').then((res)=>{
				alert(JSON.stringify(res))
			})
		}

	}
}