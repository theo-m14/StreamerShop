import React, { useEffect, useState } from "react";

export default function OrderItem({ order, setOrders, orders }) {
    const [isSelected, setIsSelected] = useState(false);

    const handleSelect = () => {
        setIsSelected(!isSelected);
        setOrders(orders.map((o) => o.id === order.id ? { ...o, isSelected: !isSelected } : o));
    }

    return (
        <tr className={`orderItem ${isSelected ? 'is-selected' : ''}`} onClick={handleSelect}>
                    {order.statut.statut === "paid" && <td onClick={handleSelect}><input type="checkbox" name="" id="" checked={isSelected} onChange={handleSelect} /></td>}
                    <td>{order.total} €</td>
                    <td>{order.createdAt.split('T')[0].split('-').reverse().join('/')} à {order.createdAt.split('T')[1].split(':')[0]}:{order.createdAt.split('T')[1].split(':')[1]}</td>

                    <td className="product">
                        {order.orderItem.map((item) => (
                            <div key={item.id}>
                                <p>{item.product.title} x{item.quantity}</p>
                            </div>
                        ))}
                    </td>

                    <td className="adress">
                        <p>{order.adress.contact.firstName} {order.adress.contact.lastName}</p>
                        <p>{order.adress.postalCode}</p>
                        <p>{order.adress.city}</p>
                        <p>{order.adress.adressLine}</p>
                    </td>
                </tr>
    )
}