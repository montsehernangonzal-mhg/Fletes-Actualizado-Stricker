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
		delete_flete(id) { return this.eliminar_recurso('fletes', 'Fletes', id, 'este flete'); },

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
		},


		clienteEditando: null,
		formEditarCliente: { nombre: '', telefono: '', saldo_pendiente: 0 },
		mensaje_cliente: '',

		editar_cliente(c) {
			this.mensaje_cliente = '';
			this.clienteEditando = c.id;
			this.formEditarCliente = {
				nombre: c.nombre,
				telefono: c.telefono,
				saldo_pendiente: c.saldo_pendiente
			};
		},

		cancelar_edicion_cliente() {
			this.clienteEditando = null;
			this.mensaje_cliente = '';
		},

		async actualizar_cliente() {
			this.mensaje_cliente = '';
			if (!this.formEditarCliente.nombre.trim()) {
				this.mensaje_cliente = 'El nombre es obligatorio';
				return;
			}
			try {
				const res = await fetch(`api.php?recurso=clientes&id=${this.clienteEditando}`, {
					method: 'PUT',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						...this.formEditarCliente,
						saldo_pendiente: Number(this.formEditarCliente.saldo_pendiente)
					})
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.error || data.mensaje || 'No se pudo actualizar el cliente');
				this.clienteEditando = null;
				await this.cargarRecurso('clientes', 'Clientes');
			} catch (error) {
				this.mensaje_cliente = error.message;
			}
		},

		// ===== EDITAR CHOFERES =====
		choferEditando: null,
		formEditarChofer: { nombre: '', licencia: '', estatus: 'Disponible' },
		mensaje_chofer: '',

		editar_chofer(ch) {
			this.mensaje_chofer = '';
			this.choferEditando = ch.id;
			this.formEditarChofer = { nombre: ch.nombre, licencia: ch.licencia, estatus: ch.estatus };
		},

		cancelar_edicion_chofer() {
			this.choferEditando = null;
			this.mensaje_chofer = '';
		},

		async actualizar_chofer() {
			this.mensaje_chofer = '';
			if (!this.formEditarChofer.nombre.trim() || !this.formEditarChofer.licencia.trim()) {
				this.mensaje_chofer = 'Nombre y licencia son obligatorios';
				return;
			}
			try {
				const res = await fetch(`api.php?recurso=choferes&id=${this.choferEditando}`, {
					method: 'PUT',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(this.formEditarChofer)
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.error || data.mensaje || 'No se pudo actualizar el chofer');
				this.choferEditando = null;
				await this.cargarRecurso('choferes', 'Choferes');
			} catch (error) {
				this.mensaje_chofer = error.message;
			}
		},

		// ===== EDITAR UNIDADES =====
		unidadEditando: null,
		formEditarUnidad: { placas: '', modelo: '', estatus: 'Disponible' },
		mensaje_unidad: '',

		editar_unidad(u) {
			this.mensaje_unidad = '';
			this.unidadEditando = u.id;
			this.formEditarUnidad = { placas: u.placas, modelo: u.modelo, estatus: u.estatus };
		},

		cancelar_edicion_unidad() {
			this.unidadEditando = null;
			this.mensaje_unidad = '';
		},

		async actualizar_unidad() {
			this.mensaje_unidad = '';
			if (!this.formEditarUnidad.placas.trim()) {
				this.mensaje_unidad = 'Las placas son obligatorias';
				return;
			}
			try {
				const res = await fetch(`api.php?recurso=unidades&id=${this.unidadEditando}`, {
					method: 'PUT',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify(this.formEditarUnidad)
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.error || data.mensaje || 'No se pudo actualizar la unidad');
				this.unidadEditando = null;
				await this.cargarRecurso('unidades', 'Unidades');
			} catch (error) {
				this.mensaje_unidad = error.message;
			}
		},

		// ===== EDITAR FLETES =====
		fleteEditando: null,
		formEditarFlete: { cliente_id: '', chofer_id: '', unidad_id: '', origen: '', destino: '', costo: '', estatus: 'Pendiente' },
		mensaje_edicion_flete: '',

		editar_flete(f) {
			this.mensaje_edicion_flete = '';
			this.fleteEditando = f.id;
			this.formEditarFlete = {
				cliente_id: f.cliente_id ?? '',
				chofer_id: f.chofer_id ?? '',
				unidad_id: f.unidad_id ?? '',
				origen: f.origen,
				destino: f.destino,
				costo: f.costo,
				estatus: f.estatus
			};
		},

		cancelar_edicion_flete() {
			this.fleteEditando = null;
			this.mensaje_edicion_flete = '';
		},

		async actualizar_flete() {
			this.mensaje_edicion_flete = '';
			const f = this.formEditarFlete;
			if (!f.origen.trim() || !f.destino.trim() || f.costo === '') {
				this.mensaje_edicion_flete = 'Origen, destino y costo son obligatorios';
				return;
			}
			try {
				const res = await fetch(`api.php?recurso=fletes&id=${this.fleteEditando}`, {
					method: 'PUT',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({
						...f,
						cliente_id: f.cliente_id ? Number(f.cliente_id) : null,
						chofer_id: f.chofer_id ? Number(f.chofer_id) : null,
						unidad_id: f.unidad_id ? Number(f.unidad_id) : null,
						costo: Number(f.costo)
					})
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.error || data.mensaje || 'No se pudo actualizar el flete');
				this.fleteEditando = null;
				// Se recarga todo porque cambiar un flete puede modificar choferes, unidades y saldos
				await this.cargarTodo();
			} catch (error) {
				this.mensaje_edicion_flete = error.message;
			}
		},
	};
}

