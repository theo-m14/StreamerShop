import React, { useEffect } from "react";

export default function OrderItem({ order }) {
    // useEffect(() => {
    //     console.log(order);
    // }, [order]);

    return (
        <tr className="orderItem" >
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
                        <p>{order.adress.adressLine}</p>
                        <p>{order.adress.city}</p>
                        <p>{order.adress.postalCode}</p>
                    </td>
                </tr>
    )
}