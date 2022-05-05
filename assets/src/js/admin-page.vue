<script>
/* global Outdated_Pages */

const nowTime = new Date();
const rtf = new Intl.RelativeTimeFormat( 'es', {
	numeric: 'auto'
} );
const dateFormatter = new Intl.DateTimeFormat('es');

export default {
	beforeMount() {
		this.modifiedYearsAgo = 2;
	},
	data() {
		return {
			isLoading: false,
			total: 0,
			totalPages: 0,
			items: [],
			checked: [],
			modifiedYearsAgo: 0,
			query: {
				orderby: 'modified',
				order: 'asc',
				_fields: 'id,title,date,modified,link,status,parent,author,_links',
				_embed: 1,
				per_page: 25,
				page: 1
			}
		};
	},
	methods: {
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
		deleteCheckedPages( ) {
			// @todo
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
			handler( newQuery ) {
				const requestUri = new URL( Outdated_Pages.requestUri );
				const requestParams = new URLSearchParams( this.query );
				requestUri.search = requestParams.toString();
				this.isLoading = true;
				window.fetch(
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
					this.isLoading = false;
				} );
			}
		}
	}
}
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
				<button
					class="button-secondary"
					type="button"
				>
					Verificar enlaces
				</button> <button
					class="button-secondary button-trash"
					type="button"
					v-bind:disabled="! checked.length"
					v-on:click="deleteCheckedPages()"
				>
					Enviar a papelera páginas seleccionadas
				</button>
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
						<span class="screen-reader-text">Seleccionar</span>
					</th>
					<th class="column-title">Página</th>
					<th v-bind:class="[
						this.query.orderby === 'date' ? 'sorted sortable' : 'sortable',
						this.query.orderby === 'date' && this.query.order === 'asc' ? 'asc' : '',
						this.query.orderby === 'date' && this.query.order === 'desc' ? 'desc' : ''
					]"
						v-on:click="toggleSort('date')"
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
					>
						<span>Última actualización</span>
						<span v-if="this.query.orderby === 'modified'" class="sorting-indicator"></span>
					</th>
					<th>Autor/a</th>
					<th>Enlaces entrantes</th>
				</tr>
			</thead>
			<tbody>
				<tr
					v-for="item in items"
					:key="item.id"
					v-bind:class="[ isSelected( item.id ) ? 'selected' : '' ]"
					v-on:click="toggleRow( item.id )"
				>
					<td class="check-column">
						<input type="checkbox" v-bind:id="'page-check--'+item.id" v-model="checked" v-bind:value="item.id">
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
								<button type="button" class="row-action">Enviar a papelera</button>
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
					<td>
						<span class="dashicons dashicons-cross"></span>
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
			.sorting-indicator {
				display: inline-block;
				margin-top: 0;
			}
		}
	}
	tbody {
		tr {
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
}
</style>
