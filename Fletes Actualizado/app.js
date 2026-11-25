function contexto() {
	return {
		// Colecciones
		Choferes: [],
		Clientes: [],
		Fletes: [],
		Unidades: [],

		// Formularios
		formChofer: { nombre: '', licencia: '', estatus: 'Disponible' },
		formCliente: { nombre: '', telefono: '', saldo_pendiente: 0 },
		formFlete: { cliente_id: '', chofer_id: '', unidad_id: '', origen: '', destino: '', costo: '', estatus: 'Pendiente' },
		formUnidad: { placas: '', modelo: '', estatus: 'Disponible' },

		async init() {
			await this.cargarTodo();
		},

		async cargarTodo() {
			await Promise.all([
				this.cargarRecurso('clientes', 'Clientes'),
				this.cargarRecurso('choferes', 'Choferes'),
				this.cargarRecurso('unidades', 'Unidades'),
				this.cargarRecurso('fletes', 'Fletes')
			]);
		},

		async cargarRecurso(recurso, propiedad) {
			try {
				const res = await fetch(`api.php?recurso=${recurso}`);
				if (!res.ok) {
					throw new Error(`Error HTTP ${res.status} en ${recurso}`);
				}
				this[propiedad] = await res.json();
			} catch (e) {
				console.error(`Error al cargar ${recurso}:`, e);
			}
		},

		obtener_nombre_chofer(id) {
			const chofer = this.Choferes.find(c => Number(c.id) === Number(id));
			return chofer ? chofer.nombre : 'Sin asignar';
		},

		obtener_info_unidad(id) {
			const unidad = this.Unidades.find(u => Number(u.id) === Number(id));
			return unidad ? `${unidad.placas} (${unidad.modelo})` : 'Sin asignar';
		},

		obtener_nombre_cliente(id) {
			const cliente = this.Clientes.find(cl => Number(cl.id) === Number(id));
			return cliente ? cliente.nombre : 'Sin cliente';
		},

		formato_dinero(monto) {
			const valor = Number(monto || 0);
			return valor.toLocaleString('es-MX', {
				style: 'currency',
				currency: 'MXN'
			});
		}
	};
}
