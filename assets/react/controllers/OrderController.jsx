import React, { useState } from 'react';
import OrderItem from '../components/OrderItem';
import ShipmentForm from '../components/ShipmentForm';

export default function ({ initialOrders }) {

    const [orders,setOrders] = useState(JSON.parse(initialOrders));

    const [currentStatusDisplay, setCurrentStatusDisplay] = useState("paid");

    const [shipment,setShipment] = useState({
        adress: null,
        orders: [],
        productConfirmation: false,
        packages: [
            {
                type: null,
                dimension: {
                    width: null,
                    height: null,
                    length: null,
                    weight: null,
                },
                content: null,
                stackable: null,
                externalId: null,
            }
        ],
    });

    const handleCreateShipmentForm = () => {
        const selectedOrders = orders.filter((order) => order.isSelected);
        setShipment({...shipment, orders: selectedOrders});
        document.getElementById("createShipmentModal").classList.add("is-active");
        
    }

    return (
        <>
            <div>
                <div className="tabs is-centered is-toggle is-toggle-rounded">
                    <ul>
                    <li className={currentStatusDisplay == "paid" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("paid")}>Payées</a></li>
                    <li className={currentStatusDisplay == "waitingPayment" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("waitingPayment")}>En attente de paiement</a></li>
                    <li className={currentStatusDisplay == "pending" ? "is-active" : ""}><a className="is-active" onClick={() => setCurrentStatusDisplay("pending")}>En attente</a></li>
                </ul>
                </div>
                {/* Afficher le titre en fonction du status en français pas paid mais payées*/}
                <h2 className="has-text-centered is-size-4 mt-6 mb-6">Commandes {currentStatusDisplay === "paid" ? "payées" : currentStatusDisplay === "waitingPayment" ? "en attente de paiement" : "en attente"}</h2>
                <div className="table-container">
                <table className="table is-striped is-fullwidth has-text-centered">
                    <thead>
                        <tr>
                            {currentStatusDisplay === "paid" && <th className='has-text-centered'></th>}
                            <th className='has-text-centered'>Total</th>
                            <th className='has-text-centered'>Date</th>
                            <th className='has-text-centered'>Produits</th>
                            <th className='has-text-centered'>Adresse</th>      
                        </tr>
                    </thead>
                        <tbody>
                            {orders.filter((order) => order.statut.statut === currentStatusDisplay).map((order) => (
                                <OrderItem order={order} key={order.id} setOrders={setOrders} orders={orders} />
                            ))}
                            
                        </tbody>
                        <tfoot>
                            {currentStatusDisplay === "paid" && <tr><td className="has-text-centered"><button className='button is-primary' onClick={handleCreateShipmentForm}>Créer une expédition</button></td></tr>}
                        </tfoot>
                </table>
                </div>
            </div>
            <div className="modal" id="createShipmentModal">
                <div className="modal-background" onClick={() => document.getElementById("createShipmentModal").classList.remove("is-active")}></div>
                <div className="modal-content">
                    <ShipmentForm shipment={shipment} orders={orders} setShipment={setShipment} />
                </div>
                <div className="modal-close is-large" aria-label="close" onClick={() => document.getElementById("createShipmentModal").classList.remove("is-active")}></div>
            </div>
        </>                
    )
}

