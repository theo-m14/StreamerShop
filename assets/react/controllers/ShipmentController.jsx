import React, { useState} from 'react';
import ShipmentItem from '../components/ShipmentItem';
export default function ShipmentController({ initialShipments, currentUser }) {
    const [shipments, setShipments] = useState(JSON.parse(initialShipments));
    const [user, setUser] = useState(JSON.parse(currentUser));

    const [currentStatusDisplay, setCurrentStatusDisplay] = useState("pending");

    console.log(shipments);
    console.log(user);

    const shipmentDisplayTitle = {
        pending: "En attente",
        shipped: "Expédiées",
        delivered: "Livrées"
    };



    return (
        <div>
            <div className="tabs is-centered is-toggle is-toggle-rounded">
                <ul>
                    <li className={currentStatusDisplay === "pending" ? "is-active" : ""}><a onClick={() => setCurrentStatusDisplay("pending")}>En attente</a></li>
                    <li className={currentStatusDisplay === "shipped" ? "is-active" : ""}><a onClick={() => setCurrentStatusDisplay("shipped")}>Expédiées</a></li>
                    <li className={currentStatusDisplay === "delivered" ? "is-active" : ""}><a onClick={() => setCurrentStatusDisplay("delivered")}>Livrées</a></li>
                </ul>
            </div>
            <h2 className="has-text-centered is-size-4 mt-6 mb-6">{shipmentDisplayTitle[currentStatusDisplay]}</h2>
            <div className="table-container">
                <table className="table is-striped is-fullwidth has-text-centered">
                    <thead>
                        <tr>
                            <th className='has-text-centered'>Date</th>
                            <th className='has-text-centered'>Prix</th>
                            <th className='has-text-centered'>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {shipments.filter((shipment) => shipment.statut.statut === currentStatusDisplay).map((shipment) => (
                            <ShipmentItem shipment={shipment} key={shipment.id} />
                        ))}
                    </tbody>
                </table>
            </div>

        </div>
    );
}

