import React, { useState } from 'react';
import ProductCard from '../components/ProductCard';
import Cart from '../components/Cart';
import useCart from '../hooks/useCart';
import {loadStripe} from '@stripe/stripe-js';
import Checkout from '../components/Checkout';

export default function ({initialProducts, username}) {
    let productsArray = JSON.parse(initialProducts);

    const [clientSecret, setClientSecret] = useState(null);

    const [products, setProducts] = useState(productsArray);

    const stripePromise = loadStripe('pk_test_51O0mx9Ddah1uLYmzf0SMrTBkSFADmF9sR63AdXlea9ZT8OOfMrzhQIBb62SVTIiTwrnbYbV8QXPJsvtooYRudZb600UHHJ3MFq');


    //Fonction pour mettre à jour les produits
    const updateProduct = (updatedProduct) => {
        setProducts(products.map(product => 
            product.id === updatedProduct.id ? updatedProduct : product
        ));
    };

    const { cart, addToCart, removeFromCart } = useCart(updateProduct);

    return (
        <>
        {clientSecret ? (
            <div>
                <Checkout clientSecret={clientSecret} stripePromise={stripePromise} />
            </div>
        ) : (
            <>
            <div className="shop-container">
                {products.map((product) => (
                <ProductCard key={product.id} product={product} addToCart={addToCart} />
            ))}
        </div>
        <Cart cart={cart} removeFromCart={removeFromCart} setClientSecret={setClientSecret} />
            </>
        )}
        </>
    );
}