import React, { useState } from 'react'

export default function () {
    const [apiKey, setApiKey] = useState('');
    const [apiSecret, setApiSecret] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [deliveryPrice, setDeliveryPrice] = useState(0);  


    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsLoading(true);
        
        try {
            const formData = new FormData();
            formData.append('api_key', apiKey);
            formData.append('api_secret', apiSecret);
            
            const response = await fetch('/onboarding/boxtal', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                window.location.href = '/onboarding';
            } else {
                setMessage({
                    type: 'error',
                    text: 'Une erreur est survenue lors de la configuration de Boxtal'
                });
            }
        } catch (error) {
            setMessage({
                type: 'error',
                text: 'Une erreur est survenue lors de la connexion au serveur'
            });
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="container is-max-desktop">
            <div className="box">
                <h1 className="title is-4 has-text-centered">Configuration de Boxtal</h1>
                
                {message && (
                    <div className={`notification ${message.type === 'error' ? 'is-danger' : 'is-success'}`}>
                        <button className="delete" onClick={() => setMessage(null)}></button>
                        {message.text}
                    </div>
                )}
                
                <div className="content">
                    <p>
                        Pour commencer à accepter des commandes, vous devez vous connecter à votre compte Boxtal.
                        Boxtal est une plateforme de livraison rapide et efficace.
                    </p>
                    
                    <div className="steps">
                        <div className="step-item">
                            <div className="step-details">
                                <p className="step-title is-size-5 has-text-weight-bold"> Etape 1: Créer un compte Boxtal</p>
                                <div className="has-text-centered mt-2">
                                    <a href="https://redirect.boxtal.com/iam/app-redirect/register?app=shipping&profile=default" 
                                       className="button is-primary is-outlined" 
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <span className="icon">
                                            <i className="fas fa-user-plus"></i>
                                        </span>
                                        <span>Créer un compte Boxtal</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div className="step-item mt-5">
                            <div className="step-details">
                                <p className="step-title is-size-5 has-text-weight-bold"> Etape 2: Se connecter à Boxtal</p>
                                <div className="has-text-centered mt-2">
                                    <a href="https://shipping.boxtal.com/fr/fr/accueil" 
                                       className="button is-primary is-outlined" 
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <span className="icon">
                                            <i className="fas fa-sign-in-alt"></i>
                                        </span>
                                        <span>Se connecter à Boxtal</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div className="step-item mt-5">
                            <div className="step-details">
                                <p className="step-title is-size-5 has-text-weight-bold"> Etape 3: Configurer les clés API</p>
                                <p className="is-size-6">
                                    Se rendre dans l'espace <strong>"Développeur"</strong> puis <strong>"Applications"</strong> et cliquer sur <strong>"Nouvelle application"</strong> 
                                    de type <strong>"Api V3"</strong> puis copier vos clés API.
                                </p>
                                <div className="has-text-centered mt-2">
                                    <a href="https://developer.boxtal.com/fr/fr/applications" 
                                       className="button is-primary is-outlined" 
                                       target="_blank" 
                                       rel="noopener noreferrer">
                                        <span className="icon">
                                            <i className="fas fa-cog"></i>
                                        </span>
                                        <span>Configurer les clés API</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form onSubmit={handleSubmit} className="mt-6">
                        <div className="field">
                            <label htmlFor="apiKey" className="label">Clé d'accès</label>
                            <div className="control has-icons-left">
                                <input 
                                    id="apiKey"
                                    type="text" 
                                    className="input" 
                                    value={apiKey} 
                                    onChange={(e) => setApiKey(e.target.value)}
                                    required
                                />
                                <span className="icon is-small is-left">
                                    <i className="fas fa-key"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div className="field">
                            <label htmlFor="apiSecret" className="label">Clé secrète</label>
                            <div className="control has-icons-left">
                                <input 
                                    id="apiSecret"
                                    type="text" 
                                    className="input" 
                                    value={apiSecret} 
                                    onChange={(e) => setApiSecret(e.target.value)}
                                    required
                                />
                                <span className="icon is-small is-left">
                                    <i className="fas fa-lock"></i>
                                </span>
                            </div>
                        </div>

                        <div className="field">
                            <p className="is-size-6">En vous basant sur le prix d'expédition de l'offre MondialRelay CpourToi de cette grille tarifaire, veuillez indiquer le prix de livraison</p>
                            <a href="https://resource.boxtal.com/documents/tarifs/tarifs-boxtal.pdf" target="_blank" rel="noopener noreferrer">Grille tarifaire</a>
                            <label htmlFor="deliveryPrice" className="label">Prix de livraison</label>
                            <div className="control has-icons-left">
                                <input 
                                    id="deliveryPrice"
                                    type="number"
                                    className="input"
                                    value={deliveryPrice}
                                    onChange={(e) => setDeliveryPrice(e.target.value)}
                                    required
                                />
                                <span className="icon is-small is-left">
                                    <i className="fas fa-money-bill"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div className="field mt-5">
                            <div className="buttons is-centered">
                                <button 
                                    type="submit" 
                                    className={`button is-primary ${isLoading ? 'is-loading' : ''}`}
                                    disabled={!apiKey || !apiSecret || isLoading}
                                >
                                    Enregistrer les clés API
                                </button>
                            </div>
                        </div>


                    </form>
                </div>
            </div>
        </div>
    )
}

