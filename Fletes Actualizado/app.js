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

		/*init(){
			//aqui consumimos el api y llenamos las colecciones
			fetch('api.php').then((res)=>{
				alert(JSON.stringify(res))
			})
		}*/

		async init() {
			try {
				const res = await fetch('api.php');
				if (!res.ok) {
					throw new Error(`Error HTTP: ${res.status}`);
				}
				this.Clientes = await res.json();
			} catch (e) {
				console.error("Error al cargar clientes:", e);
			}
		}
	}
}
