import React, { useState } from 'react';
import OrderItem from '../components/OrderItem';
export default function ({ initialOrders }) {
    let ordersArray = JSON.parse(initialOrders);

    const [currentStatusDisplay, setCurrentStatusDisplay] = useState("paid");

    console.log(ordersArray);

    return (
        <div key={ordersArray.id} style={{ margin: "auto", width: "100%" }}>
            <div className="tabs is-centered is-toggle is-toggle-rounded">
                <ul>
                    <li className={currentStatusDisplay == "paid" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("paid")}>Payées</a></li>
                    <li className={currentStatusDisplay == "waitingPayment" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("waitingPayment")}>En attente de paiement</a></li>
                    <li className={currentStatusDisplay == "pending" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("pending")}>En attente</a></li>
                </ul>
            </div>
            {/* Afficher le titre en fonction du status en français pas paid mais payées*/}
            <h2 className="has-text-centered is-size-4 mt-6 mb-6">Commandes {currentStatusDisplay === "paid" ? "payées" : currentStatusDisplay === "waitingPayment" ? "en attente de paiement" : "en attente"}</h2>
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
