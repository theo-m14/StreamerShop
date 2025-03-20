import React from 'react';

const ShipmentItem = ({ shipment }) => {

    const getShipmentDocument = async (shipmentId) => {
        const response = await fetch(`/vendor/shipment/${shipmentId}/document`)
        .then(response => response.json())
        .then(data => {
            console.log(data);
            let url = data.content[0].url;
            console.log(url);
            //Ici on va ouvrir une nouvelle fenêtre avec le document
            window.open(url, '_blank');
        });
    }

    return (
        <tr>
            <td>{shipment.createdAt.split('T')[0].split('-').reverse().join('/') + ' à ' + shipment.createdAt.split('T')[1].split(':')[0] + ':' + shipment.createdAt.split('T')[1].split(':')[1]}</td>
            <td>{shipment.deliveryPrice} €</td>
            <td>
                <div className="buttons is-centered">
                    {shipment.statut.statut === "pending" && (
                        <button className="button is-primary" onClick={() => {
                            getShipmentDocument(shipment.shipmentId);
                        }}>

                    Document expédition
                    <i className="fa-solid fa-print ml-2"></i>
                    </button>
                    )}
                    {shipment.statut.statut !== "pending" && (
                        <a href={shipment.packageTrackingUrl} className="button is-primary" target="_blank" rel="noopener noreferrer">
                        Suivi
                        <i className="fa-solid fa-truck ml-2"></i>
                        </a>
                    )}
                </div>
            </td>
        </tr>
    );
};

export default ShipmentItem;
