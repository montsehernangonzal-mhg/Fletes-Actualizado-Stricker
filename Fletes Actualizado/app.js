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

		async eliminar_recurso(recurso, propiedad, id, etiqueta) {
			if (!confirm(`¿Estás seguro de eliminar ${etiqueta}?`)) return;
			try {
				const res = await fetch(`api.php?recurso=${recurso}&id=${id}`, {
					method: 'DELETE'
				});
				const data = await res.json().catch(() => ({}));
				if (!res.ok) {
					alert(data.error || data.mensaje || `No se pudo eliminar (HTTP ${res.status})`);
					return;
				}
				await this.cargarRecurso(recurso, propiedad);
			} catch (e) {
				console.error(`Error al eliminar ${recurso}:`, e);
			}
		},

		delete_cliente(id) { return this.eliminar_recurso('clientes', 'Clientes', id, 'este cliente'); },
		delete_unidad(id)  { return this.eliminar_recurso('unidades', 'Unidades', id, 'esta unidad'); },
		delete_chofer(id)  { return this.eliminar_recurso('choferes', 'Choferes', id, 'este chofer'); },

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

		mensaje_flete: '',

		async guardar_flete() {
			this.mensaje_flete = '';
			try {
				const res = await fetch('api.php?recurso=fletes', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						...this.formFlete,
						costo: Number(this.formFlete.costo)
					})
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.error || 'no se pudo guardar el flete');
				this.mensaje_flete = 'flete guardado';
				this.formFlete = { cliente_id: '', chofer_id: '', unidad_id: '', origen: '', destino: '', costo: '', estatus: 'Pendiente' };
				await this.cargarTodo();
			} catch (error) {
				this.mensaje_flete = error.message;
			}
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

