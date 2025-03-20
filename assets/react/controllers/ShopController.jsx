import React, { useState } from 'react';
import ProductCard from '../components/ProductCard';
import Cart from '../components/Cart';
import useCart from '../hooks/useCart';
import Checkout from '../components/Checkout';

export default function ({initialProducts}) {
    let productsArray = JSON.parse(initialProducts);

    const [products, setProducts] = useState(productsArray);

    const [cartisValidate, setCartisValidate] = useState(false);

    


    //Fonction pour mettre à jour les produits
    const updateProduct = (updatedProduct) => {
        setProducts(products.map(product => 
            product.id === updatedProduct.id ? updatedProduct : product
        ));
    };

    const { cart, addToCart, removeFromCart } = useCart(updateProduct);

    return (
        <>
        {cartisValidate ? (
            <div>
                <Checkout cart={cart} />
            </div>
        ) : (
            <>
            <div className="shop-container is-flex is-flex-wrap-wrap is-justify-content-space-evenly container">
                {products.map((product) => (
                    <div key={product.id} className="is-flex is-flex-direction-column" style={{width: '300px', margin: '1rem'}}>
                        <ProductCard product={product} addToCart={addToCart} />
                    </div>
                ))}
            </div>
            <Cart cart={cart} removeFromCart={removeFromCart} setCartisValidate={setCartisValidate}/>
            </>
        )}
        </>
    );
}