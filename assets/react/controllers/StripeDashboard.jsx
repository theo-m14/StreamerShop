import React, { useState } from 'react';
import NotificationBannerUI from "../components/stripe/NotificationBannerUI";
import { useStripeConnect } from "../hooks/useStripeConnect";
import AccountManagementUI from "../components/stripe/AccountManagementUI";
import PaymentManagementUI from "../components/stripe/PaymentManagementUI";
import BalancesManagementUI from "../components/stripe/BalancesManagementUI";
import DocumentManagementUI from "../components/stripe/DocumentManagementUI";


export default function StripeDashboard({connectedAccountId}) {
    const stripeConnectInstance = useStripeConnect(connectedAccountId)
    const [activeTab, setActiveTab] = useState('account');

    return (
        <div>
            {stripeConnectInstance && <NotificationBannerUI stripeConnectInstance={stripeConnectInstance} />}
            {stripeConnectInstance && <BalancesManagementUI stripeConnectInstance={stripeConnectInstance} />}
            {stripeConnectInstance && <div className="tabs is-centered is-toggle is-toggle-rounded">
                <ul className="mt-4">
                    <li className={activeTab === 'account' ? 'is-active' : ''} onClick={() => setActiveTab('account')}>
                        <a>Compte</a>
                    </li>
                    <li className={activeTab === 'payment' ? 'is-active' : ''} onClick={() => setActiveTab('payment')}>
                        <a>Paiement</a>
                    </li>
                    <li className={activeTab === 'document' ? 'is-active' : ''} onClick={() => setActiveTab('document')}>
                        <a>Document</a>
                    </li>
                </ul>
            </div>}
            {stripeConnectInstance && activeTab === 'account' && <AccountManagementUI stripeConnectInstance={stripeConnectInstance} />}
            {stripeConnectInstance && activeTab === 'payment' && <PaymentManagementUI stripeConnectInstance={stripeConnectInstance} />}
            {stripeConnectInstance && activeTab === 'document' && <DocumentManagementUI stripeConnectInstance={stripeConnectInstance} />}
        </div>
        )
}
