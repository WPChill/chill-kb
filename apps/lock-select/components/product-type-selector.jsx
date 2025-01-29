import { ComboboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useKnowledgeBaseState } from '../state/use-knowledge-base-state';
import { setSelectedProducts } from '../state/actions';
import styles from './lock-select.module.scss';

export default function ProductTypeSelector( { productType } ) {
	const { state, dispatch } = useKnowledgeBaseState();
	const { selectedProducts } = state;

	const selectedProductsForType =
        selectedProducts.find( ( item ) => item.key === productType.slug )?.products || [];

	const validSelectedProducts = selectedProductsForType.filter( ( product ) =>
		productType.products.some( ( p ) => p.value === product ),
	);

	const handleAdd = ( newValue ) => {
		if ( ! newValue || selectedProductsForType.includes( newValue ) ) {
			return;
		}
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
				label={ __( `Search`, 'wpchill-kb' ) + ` ${ productType.name }` }
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
							aria-label={ __( 'Remove Product', 'wpchill-kb' ) }
						>
							&times;
						</button>
					</li>
				) ) }
			</ul>
		</div>
	);
}
