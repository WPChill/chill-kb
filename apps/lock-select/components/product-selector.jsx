import { ComboboxControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useModulaState } from '../state/use-modula-state';
import { setSelectedProducts } from '../state/actions';
import { useAccessProducts } from '../query/useAccessProducts';
import styles from './lock-select.module.scss';

function ProductTypeSelector( { productType } ) {
	const { state, dispatch } = useModulaState();
	const { selectedProducts } = state;

	const selectedProductsForType =
        selectedProducts.find( ( item ) => item.key === productType.slug )?.products || [];

	const validSelectedProducts = selectedProductsForType.filter( ( product ) =>
		productType.products.some( ( p ) => p.value === product ),
	);

	const handleAdd = ( newValue ) => {
		if ( newValue && ! selectedProductsForType.includes( newValue ) ) {
			const updatedSelectedProducts = selectedProducts.map( ( item ) => {
				if ( item.key === productType.slug ) {
					return { ...item, products: [ ...item.products, newValue ] };
				}
				return item;
			} );

			if ( ! updatedSelectedProducts.some( ( item ) => item.key === productType.slug ) ) {
				updatedSelectedProducts.push( { key: productType.slug, products: [ newValue ] } );
			}

			dispatch( setSelectedProducts( updatedSelectedProducts ) );
		}
	};

	const handleRemove = ( valueToRemove ) => {
		const updatedSelectedProducts = selectedProducts.map( ( item ) => {
			if ( item.key === productType.slug ) {
				return {
					...item,
					products: item.products.filter( ( product ) => product !== valueToRemove ),
				};
			}
			return item;
		} );

		dispatch( setSelectedProducts( updatedSelectedProducts ) );
	};

	return (
		<div>
			<ComboboxControl
				multiple
				label={ __( `Search`, 'text-domain' ) + ` ${ productType.name }` }
				value={ null }
				onChange={ handleAdd }
				allowReset={ false }
				options={ productType.products.filter(
					( product ) => ! selectedProductsForType.includes( product.value ),
				) }
			/>
			<ul className={ styles.selectedProducts } >
				{ validSelectedProducts.map( ( product ) => (
					<li className={ styles.selectedProduct } key={ product }>
						{ productType.products.find( ( p ) => p.value === product )?.label || product }
						<button
							className={ styles.selectedProductButton }
							type="button"
							onClick={ () => handleRemove( product ) }
							aria-label={ __( 'Remove Product', 'text-domain' ) }
						>
							&times;
						</button>
					</li>
				) ) }
			</ul>
		</div>
	);
}

export default function ProductSelector() {
	const { state } = useModulaState();
	const { postId, selectedProducts } = state;
	const { data, isLoading, error } = useAccessProducts( postId );

	if ( isLoading ) {
		return (
			<div className={ styles.spinnerWrapper }>
				<Spinner className={ styles.spinner } />
			</div>
		);
	}

	if ( error || ! data ) {
		return null;
	}

	return (
		<div>
			{ data.map( ( productType ) => (
				<ProductTypeSelector
					key={ productType.slug }
					productType={ productType }
				/>
			) ) }
			<input
				type="hidden"
				name={ `wpchill_kb_access_products` }
				value={ JSON.stringify( selectedProducts ) }
			/>
		</div>
	);
}
