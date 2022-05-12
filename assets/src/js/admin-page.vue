<script>
/* global Outdated_Pages */

import { defineComponent } from "vue";

const PER_PAGE = 25;
const nowTime = new Date();
const rtf = new Intl.RelativeTimeFormat( 'es', {
	numeric: 'auto'
} );
const dateFormatter = new Intl.DateTimeFormat('es');
let refreshDataInterval;

export default defineComponent( {
	beforeMount() {
		this.modifiedYearsAgo = 2;
	},
	data() {
		return {
			PER_PAGE: PER_PAGE,
			isLoading: false,
			isWorking: false,
			isCountingLinks: false,
			showReload: false,
			currentAction: '',
			total: 0,
			totalPages: 0,
			items: [],
			checked: [],
			deleted: [],
			workingOn: [],
			hasLinks: {},
			modifiedYearsAgo: 0,
			query: {
				orderby: 'modified',
				order: 'asc',
				_fields: 'id,title,date,modified,link,status,parent,author,_links',
				_embed: 1,
				per_page: PER_PAGE,
				page: 1
			}
		};
	},
	methods: {
		toggleAll() {
			if ( this.allChecked() ) {
				this.checked = [];
			} else {
				this.checked = this.items.map( item => item.id );
			}
		},
		allChecked() {
			return this.checked.length === this.items.length;
		},
		toggleSort( column ) {
			const currentParams = JSON.parse( JSON.stringify( this.query ) );
			// cambiar modo de ordenar
			let newOrder = this.query.order;
			if ( column === this.query.orderby ) {
				newOrder = this.query.order === 'asc' ? 'desc' : 'asc';
			}
			const newOrderBy = column;
			this.query = { ...currentParams, ...{ orderby: newOrderBy, order: newOrder } };
		},
		getEditLink( itemID ) {
			const editBaseURL = new URL( Outdated_Pages.baseEditUri );
			const editURLParams = new URLSearchParams({
				action: 'edit',
				post: itemID
			});
			editBaseURL.search = editURLParams.toString();
			return editBaseURL;
		},
		toggleRow( itemID ) {
			if ( this.isSelected( itemID ) ) {
				this.checked = this.checked.filter( id => id !== itemID );
			} else {
				this.checked.push( itemID );
			}
		},
		formattedDate( time ) {
			const theDate = new Date( time );
			return dateFormatter.format( theDate );
		},
		getTimeDiff( time ) {
			const pastTime = new Date( time );
			const daysAgo = Math.round( ( pastTime - nowTime ) / 1000 / 60 / 60 / 24 );
			return rtf.format( daysAgo , 'days' );

		},
		getAuthor( item ) {
			const author = item?._embedded?.author?.[0];
			if ( ! author ) {
				return '';
			}
			return author.name;
		},
		deletePage( itemID ) {
			this.doDeletePages( [ itemID ] );
		},
		deleteCheckedPages(  ) {
			const deleteIds = [ ...this.checked ];
			this.currentAction = 'delete';
			this.doDeletePages( deleteIds ).then(
				() => {
					this.checked = [];
				}
			);
		},
		doDeletePages( itemIds ) {
			const requestUri = new URL( Outdated_Pages.ajaxDeleteEndpoint );
			requestUri.searchParams.set('ids', itemIds );
			this.isWorking = true;
			this.workingOn = [ ...itemIds ];
			return window.fetch(
				requestUri
			).then(
				resp => resp.json()
			).then(
				data => {
					// deleted: ids de páginas enviadas a papelera.
					data.data.deleted.map( item => {
						if ( this.deleted.indexOf( item ) === -1 ) {
							this.deleted.push( item );
						}
					} );
					// disminuir cantidad de páginas totales
					this.total = this.total - data.data.deleted.length;

					// recalcular cantidad de páginas disponibles
					this.totalPages = Math.ceil( this.total / PER_PAGE );
				}
			).finally(
				() => {
					this.isWorking = false;
					this.workingOn = [];
					this.currentAction = '';
				}
			);

		},
		isDeleted( itemID ) {
			return this.deleted.indexOf( itemID ) !== -1;
		},
		isSelected( itemID ) {
			return this.checked.indexOf( itemID ) !== -1;
		},
		firstPage() {
			if ( ! this.isLoading && this.query.page !== 1 ) {
				this.query.page = 1;
			}
		},
		previousPage() {
			if ( ! this.isLoading && this.query.page > 1 ) {
				this.query.page = this.query.page - 1;
			}
		},
		nextPage() {
			if ( ! this.isLoading && this.query.page < this.totalPages ) {
				this.query.page = this.query.page + 1;
			}
		},
		lastPage() {
			if ( ! this.isLoading && this.query.page !== this.totalPages ) {
				this.query.page = this.totalPages;
			}
		},
		triggerReload() {
			this.isWorking = true;
			this.currentAction = 'reload';
			this.loadItems().then( () => {
				this.isWorking = false;
				this.currentAction = '';
				this.showReload = false;
			} );
		},
		triggerCheckLinks() {
			const requestUri = new URL( Outdated_Pages.ajaxCheckLinksEndpoint );
			const pageIds = this.items.reduce( ( carry, item ) => {
				if ( this.deleted.indexOf( item.id ) === -1 ) {
					carry.push( item.id );
				}
				return carry;
			}, [] );
			requestUri.searchParams.set('ids', pageIds);
			if ( ! pageIds.length ) {
				alert( 'Carga más resultados para buscar enlaces' );
			}
			this.isCountingLinks = true;
			window.fetch(
				requestUri
			).then(
				resp => resp.json()
			).then(
				data => {
					refreshDataInterval = window.setInterval(
						() => {
							this.checkStatus();
						},
						2500
					);
				}
			);
		},
		checkStatus() {
			window.fetch( Outdated_Pages.ajaxCheckStatusEndpoint )
			.then( resp => resp.json() )
			.then(
				data => {
					this.hasLinks = data.data.has_links ? { ...data.data.has_links } : {};
					if ( data.data.status === 'finished' ) {
						this.isCountingLinks = false;
						clearInterval( refreshDataInterval );
					}
				}
			);
		},
		loadItems() {
			const requestUri = new URL( Outdated_Pages.requestUri );
			const requestParams = new URLSearchParams( this.query );
			requestUri.search = requestParams.toString();
			this.isLoading = true;
			return window.fetch(
				requestUri
			).then(
				resp => {
					this.total = parseInt( resp.headers.get('x-wp-total'), 10 );
					this.totalPages = parseInt( resp.headers.get('x-wp-totalpages'), 10 );
					return resp.json();
				}
			).then(
				data => {
					this.items = data;
				}
			).finally( () =>{
				this.checked = [];
				this.deleted = [];
				this.isLoading = false;
			} );
		}
	},
	watch: {
		modifiedYearsAgo( newModifiedYearsAgo ) {
			const now = new Date();
			now.setFullYear( now.getFullYear() - newModifiedYearsAgo );
			this.query = { ...this.query, ...{
				modified_before: now.toISOString(),
				page: 1
			} };
		},
		query: {
			deep: true,
			handler() {
				this.loadItems();
			}
		},
		deleted: {
			deep: true,
			handler() {
				this.showReload = this.deleted.length === this.items.length && this.query.page < this.totalPages;
			}
		}
	}
} );
</script>

<template>
	<div class="wrap outdated-pages-admin">
		<h1 class="wp-heading-inline">Páginas desactualizadas</h1>
		<span v-bind:class="['spinner', isLoading ? 'is-active' : '']"></span>
		<hr class="wp-header-end">
		<ul class="subsubsub">
			<li>
				Última actualización:
			</li>
			<li v-for="n in 3" :key="n">
				<span v-bind:class="['filter-list', modifiedYearsAgo === n ? 'current' : '' ]" v-on:click="this.modifiedYearsAgo = n">
					Hace {{ n }} <template v-if="n > 1">años</template><template v-else>año</template> o más
				</span><span v-if="n < 3"> | </span>
			</li>
		</ul>
		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<select v-model="this.query.per_page">
					<option v-for="n in 4" v-bind:key="n" v-bind:value="PER_PAGE * n">{{ PER_PAGE * n }}</option>
				</select> <button
					v-bind:class="[
						'button-secondary',
						isCountingLinks ? 'button-is-working' : ''
					]"
					v-on:click="triggerCheckLinks()"
					v-bind:disabled="this.deleted.length === this.items.length"
					type="button"
				>
					<template v-if="isCountingLinks">
						Verificando enlaces&hellip;
					</template>
					<template v-else>
						Verificar enlaces
					</template>
				</button> <button
					v-bind:class="[
						'button button-trash',
						isWorking && currentAction === 'delete' ? 'button-is-working' : ''
					]"
					type="button"
					v-bind:disabled="! checked.length"
					v-on:click="deleteCheckedPages()"
				>
					<template v-if="this.checked.length === 0">
						Selecciona 1 o más páginas para eliminar
					</template>
					<template v-else-if="this.checked.length === 1">
						Enviar a papelera la página seleccionada
					</template>
					<template v-else>
						Enviar a papelera las {{ this.checked.length }} páginas seleccionadas
					</template>
				</button> <button
					v-if="showReload"
					v-bind:class="[
						'button button-link',
						'button-reload'
					]"
					type="button"
					v-on:click="triggerReload()"
				> Cargar más resultados</button>
			</div>
			<h2 class="screen-reader-text">Navegación por el listado de páginas</h2>
			<div class="tablenav-pages">
				<span class="displaying-num">{{ total }} elementos</span>
				<span class="pagination-links">
					<span
						v-bind:class="['tablenav-pages-navspan button', this.query.page < 2 ? 'disabled' : '']"
						v-bind:aria-hidden="this.query.page < 2"
						v-on:click="firstPage()"
					>« <span class="screen-reader-text">Primera página </span></span> <span
						v-bind:class="['tablenav-pages-navspan button', this.query.page < 2 ? 'disabled': '']"
						v-bind:aria-hidden="this.query.page < 2"
						v-on:click="previousPage()"
					>‹ <span class="screen-reader-text">Página anterior</span></span> <span class="paging-input">
						<label for="current-page-selector" class="screen-reader-text">Página actual</label>
						<input class="current-page" id="current-page-selector" type="text" v-model="this.query.page" size="2" aria-describedby="table-paging">
						<span class="tablenav-paging-text"> de <span class="total-pages">{{ totalPages }}</span></span>
					</span>
					<span
						v-bind:class="['next-page button', this.query.page === this.totalPages ? 'disabled' : '' ]"
						v-on:click="nextPage()"
					>
						<span class="screen-reader-text">Página siguiente</span><span aria-hidden="true">›</span>
					</span> <span
						v-bind:class="['last-page button', this.query.page === this.totalPages ? 'disabled' : '' ]"
						v-on:click="lastPage()">
						<span class="screen-reader-text">Última página</span><span aria-hidden="true">»</span>
					</span>
				</span>
			</div>
			<br class="clear">
		</div>
		<table v-bind:class="['widefat striped', isLoading ? 'is-loading' : '']">
			<thead>
				<tr>
					<th class="check-column">
						<input
							type="checkbox"
							v-on:click="toggleAll()"
							v-bind:checked="allChecked()"
							v-bind:disabled="this.deleted.length === this.items.length"
						>
						<span class="screen-reader-text">Seleccionar</span>
					</th>
					<th class="column-title">Página</th>
					<th v-bind:class="[
						this.query.orderby === 'date' ? 'sorted sortable' : 'sortable',
						this.query.orderby === 'date' && this.query.order === 'asc' ? 'asc' : '',
						this.query.orderby === 'date' && this.query.order === 'desc' ? 'desc' : ''
					]"
						v-on:click="toggleSort('date')"
						title="Ordenar por fecha de publicación"
					>
						<span>Publicada</span>
						<span v-if="this.query.orderby === 'date'" class="sorting-indicator"></span>
					</th>
					<th v-bind:class="[
						this.query.orderby === 'modified' ? 'sorted sortable' : 'sortable',
						this.query.orderby === 'modified' && this.query.order === 'desc' ? 'desc' : '',
						this.query.orderby === 'modified' && this.query.order === 'asc' ? 'asc' : ''
					]"
						v-on:click="toggleSort('modified')"
						title="Ordenar por fecha de última actualización"
					>
						<span>Última actualización</span>
						<span v-if="this.query.orderby === 'modified'" class="sorting-indicator"></span>
					</th>
					<th>Autor/a</th>
					<th style="width:10em;text-align:center">Enlaces entrantes</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="item in items"
					:key="item.id"
					v-bind:class="[
						isSelected( item.id ) ? 'selected' : '',
						isDeleted( item.id ) ? 'deleted' : '',
						isWorking && workingOn.indexOf( item.id ) !== -1 ? 'working' : '',
						isCountingLinks && typeof hasLinks[ item.id ] === 'undefined' ? 'checking' : ''
					]"
					v-on:click="toggleRow( item.id )"
				>
					<td class="check-column">
						<template v-if="isDeleted( item.id )">
							<span class="dashicons dashicons-trash"></span>
						</template>
						<template v-else>
							<input type="checkbox" v-bind:id="'page-check--'+item.id" v-model="checked" v-bind:value="item.id">
						</template>
					</td>
					<td class="title column-title">
						<label v-bind:for="'page-check--'+item.id" v-on:click.prevent="">
							<b v-html="item.title.rendered"></b><br>
							<span class="description">{{ item.link }}</span>
						</label>
						<div class="row-actions">
							<span class="view">
								<a :href="item.link" class="row-action" target="_blank" rel="noreferer noopener">Abrir en ventana nueva</a>
							</span>
							<span class="edit">
								<a :href="getEditLink( item.id )" class="row-action">Editar</a>
							</span>
							<span class="trash">
								<button type="button" class="row-action" v-on:click.stop.prevent="deletePage( item.id )">Enviar a papelera</button>
							</span>
						</div>
					</td>
					<td>
						{{ getTimeDiff( new Date( item.date ) ) }}
						<span class="description">{{ formattedDate( item.date ) }}</span>
					</td>
					<td>
						{{ getTimeDiff( new Date( item.modified ) ) }}
						<span class="description">{{ formattedDate( item.modified ) }}</span>
					</td>
					<td>
						{{ getAuthor( item ) }}
					</td>
					<td class="column-status">
						<template v-if="typeof hasLinks[ item.id ] !== 'undefined' && hasLinks[ item.id ]">
							<span class="dashicons dashicons-yes-alt"></span>
							<br> Existe enlaces desde otros contenidos a esta página.
						</template>
						<template v-else-if="typeof hasLinks[ item.id ] !== 'undefined' && ! hasLinks[ item.id ]">
							<span class="dashicons dashicons-dismiss"></span>
						</template>
						<template v-else-if="isCountingLinks && typeof hasLinks[ item.id ] === 'undefined'">
							<span v-bind:class="['spinner', 'is-active']"></span>
						</template>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<style lang="scss" scoped>
.outdated-pages-admin {
	--color--danger: hsl(360, 60%, 44%);
	--color--danger--background: hsl(360, 60%, 96%);
	--color--selected: hsl(210, 67%, 96%);
	--color--highlight: hsl(210, 82%, 91%);
}
.spinner {
	float: none;
	margin-top: -10px;
}
.subsubsub {
	.filter-list {
		line-height: 2;
		padding: .2em;
		text-decoration: none;
		color: #2271b1;
		cursor: pointer;
		&:hover {
			text-decoration: underline;
		}
	}
	.current {
		font-weight: bold;
		color: black;
		text-decoration: none !important;
	}
}
.tablenav {
	&.top {
		background: #f0f0f1;
		position: sticky;
		top: 32px;
		z-index: 9;
		padding: 10px 0;
	}
}
.widefat {
	max-width: 100%;
	transition: all .25s linear;
	th,
	td {
		padding: 1rem;
		transition: .1s linear ease-in-out;
		&.column-title {
			width: auto;
		}
	}
	.check-column {
		text-align: center;
		padding-right: 0;
		vertical-align: middle;
	}
	&.is-loading {
		opacity: .25;
	}
	thead {
		td,
		th {
			white-space: nowrap;
			vertical-align: middle;
			&.sortable {
				&:hover {
					text-decoration: underline;
					cursor: pointer;
				}
			}
			&.sorted {
				display: flex;
				align-items: center;
			}
			&.check-column {
				input {
					margin: 0;
				}
			}
			.sorting-indicator {
				display: inline-block;
				margin-top: 0;
			}
		}
	}
	tbody {
		tr {
			td,
			th {
				transition: all .35s linear !important;
			}
			&:hover {
				cursor: pointer;
				td,
				th {
					background: var( --color--highlight );
				}
			}
			&.selected {
				td,
				th {
					background: var( --color--danger--background );
					box-shadow: inset 0 -1px rgba( 0, 0, 0, .05 );
				}
				input[type="checkbox"]:checked::before {
					filter: hue-rotate(150deg);
				}
			}
			&.deleted {
				td,
				th {
					opacity: .5;
				}
				.check-column::after {
					display: none !important;
				}
				.row-actions {
					visibility: hidden !important;
				}
			}
			&.working {
				td,
				th {
					background: var( --color--danger--background );
				}
				.check-column {
					&::after {
						content: '';
						display: inline-block;
						width: 1em;
						height: 1em;
						border: 2px solid var( --color--danger );
						border-top-color: transparent;
						border-radius: 1em;
						animation: rotation infinite 650ms linear;
					}
					input {
						display: none;
					}
				}
				.row-action {
					pointer-events: none;
					filter: grayscale( 1 );
					text-decoration: none !important;
					cursor: default;
				}
			}
			&.checking {
				.column-status {

				}
			}
			.column-status {
				text-align: center;
				vertical-align: middle;
			}
		}
		td {
			.description {
				display: block;
				color: #999;
			}
		}
	}
}
.row-actions {
	display: flex;
	gap: 1em;
	span + span {
		&::before {
			content: ' | ';
			color: #999;
			position: relative;
			left: -.5em;
		}
	}
	.row-action {
		text-decoration: none;
		padding: .25em 0;
		border: 0;
		background: none;
		line-height: 1;
		&:hover {
			text-decoration: underline;
			background: transparent;
			cursor: pointer;
		}
	}
}
.button-trash,
.trash button {
	color: var( --color--danger );

}
.button-trash {
	border-color: var( --color--danger );
	transition: all .25s linear;
	&:focus,
	&:hover,
	&.button-is-working {
		background: var( --color--danger );
		border-color: var( --color--danger ) !important;
		color: white;
	}
	&:focus {
		box-shadow: 0 0 0 1px var( --color--danger--background );
	}
}
.button-is-working {
	&::after {
		content: '';
		display: inline-block;
		width: 1ex;
		height: 1ex;
		border: 1px solid currentColor;
		border-top-color: transparent;
		border-radius: 1em;
		margin-left: .5ex;
		position: relative;
		animation: rotation infinite 450ms linear;
	}
}
</style>
