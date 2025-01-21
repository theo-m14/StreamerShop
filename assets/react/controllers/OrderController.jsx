import React, { useState } from 'react';
import OrderItem from '../components/OrderItem';
export default function ({ initialOrders }) {
    let ordersArray = JSON.parse(initialOrders);

    const [currentStatusDisplay, setCurrentStatusDisplay] = useState("paid");

    console.log(ordersArray);

    return (
        <div key={ordersArray.id} style={{ margin: "auto", width: "100%" }}>
            <div className="tabs is-centered">
                <ul>
                    <li className={currentStatusDisplay == "paid" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("paid")}>Payées</a></li>
                    <li className={currentStatusDisplay == "waitingPayment" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("waitingPayment")}>En attente de paiement</a></li>
                    <li className={currentStatusDisplay == "pending" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("pending")}>En attente</a></li>
                </ul>
            </div>
            <table className="table is-striped is-fullwidth has-text-centered">
                <thead>
                    <tr>
                        <th className='has-text-centered'>Total</th>
                        <th className='has-text-centered'>Date</th>
                        <th className='has-text-centered'>Produits</th>
                        <th className='has-text-centered'>Adresse</th>
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
