import React, { useState } from 'react';
import ProductCard from '../components/ProductCard';
import Cart from '../components/Cart';
import useCart from '../hooks/useCart';
import Checkout from '../components/Checkout';

export default function ({initialProducts, username}) {
    let productsArray = JSON.parse(initialProducts);

    const [clientSecret, setClientSecret] = useState(null);

    const [sessionId, setSessionId] = useState(null);

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
            <div className="shop-container">
                {products.map((product) => (
                <ProductCard key={product.id} product={product} addToCart={addToCart} />
            ))}
        </div>
        <Cart cart={cart} removeFromCart={removeFromCart} setCartisValidate={setCartisValidate}/>
            </>
        )}
        </>
    );
}