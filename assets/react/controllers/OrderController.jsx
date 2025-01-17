import React, { useState } from 'react';

export default function ({ initialOrders }) {
    let ordersArray = JSON.parse(initialOrders);

    const [currentStatusDisplay, setCurrentStatusDisplay] = useState("paid");

    console.log(ordersArray);

    return (
        <div>
            <button onClick={() => setCurrentStatusDisplay("paid")}>Payées</button>
            <button onClick={() => setCurrentStatusDisplay("waitingPayment")}>En attente de paiement</button>
            <button onClick={() => setCurrentStatusDisplay("pending")}>En attente</button>
            {ordersArray.filter((order) => order.statut.statut === currentStatusDisplay).map((order) => (
                <div key={order.id}>
                    <h2>{order.total} €</h2>
                    <h2>{order.createdAt.split('T')[0].split('-').reverse().join('/')} à {order.createdAt.split('T')[1].split(':')[0]}:{order.createdAt.split('T')[1].split(':')[1]}</h2>

                    <div className="product">
                        {order.orderItem.map((item) => (
                            <div key={item.id}>
                                <h2>{item.product.title} x{item.quantity}</h2>
                            </div>
                        ))}
                    </div>

                    <div className="adress">
                        <h2>{order.adress.contact.firstName} {order.adress.contact.lastName}</h2>
                        <h2>{order.adress.adressLine}</h2>
                        <h2>{order.adress.city}</h2>
                        <h2>{order.adress.postalCode}</h2>
                    </div>
                </div>
            ))}
        </div>
    )
}
