import React, { useState } from 'react';
import ProductCard from '../components/ProductCard';
import Cart from '../components/Cart';
import useCart from '../hooks/useCart';

export default function ({initialProducts, username}) {
    let productsArray = JSON.parse(initialProducts);

    const [products, setProducts] = useState(productsArray);

    //Fonction pour mettre à jour les produits
    const updateProduct = (updatedProduct) => {
        setProducts(products.map(product => 
            product.id === updatedProduct.id ? updatedProduct : product
        ));
    };

    const { cart, addToCart, removeFromCart } = useCart(updateProduct);

    return (
        <>
        <div className="shop-container">
            {products.map((product) => (
                <ProductCard key={product.id} product={product} addToCart={addToCart} />
            ))}
        </div>
        <Cart cart={cart} removeFromCart={removeFromCart} />
        </>
    );
}
