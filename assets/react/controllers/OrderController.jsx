import React, { useState } from 'react';
import OrderItem from '../components/OrderItem';
export default function ({ initialOrders }) {
    let ordersArray = JSON.parse(initialOrders);

    const [currentStatusDisplay, setCurrentStatusDisplay] = useState("paid");

    console.log(ordersArray);

    return (
        <div key={ordersArray.id} style={{ margin: "auto", width: "100%" }}>
            <button onClick={() => setCurrentStatusDisplay("paid")}>Payées</button>
            <button onClick={() => setCurrentStatusDisplay("waitingPayment")}>En attente de paiement</button>
            <button onClick={() => setCurrentStatusDisplay("pending")}>En attente</button>
            <table>
                <thead>
                    <tr>
                        <th>Total</th>
                        <th>Date</th>
                        <th>Produits</th>
                        <th>Adresse</th>
                    </tr>
                </thead>
                    <tbody>
                        {ordersArray.filter((order) => order.statut.statut === currentStatusDisplay).map((order) => (
                            <OrderItem order={order} key={order.id} />
                        ))}
                    </tbody>
            </table>
        </div>
    )
}
