import React, { useState, useRef, useEffect } from "react";
import { useForm } from "react-hook-form";
import { BoxtalParcelPointMap } from '@boxtal/parcel-point-map';

export default function AdressSelection({ setAdress, setClientSecret, cart }) {
    const { 
        register, 
        handleSubmit, 
        formState: { errors, isSubmitting }, 
        setValue,
        watch
    } = useForm({
        defaultValues: {
            country: 'France',
            zipCode: '',
            city: '',
            street: '',
            firstName: '',
            lastName: '',
            email: '',
            phone: '',
        }
    });

    const [parcelPoint, setParcelPoint] = useState(null);
    const boxtalParcelPointMapRef = useRef(null);

    useEffect(() => {
        fetch('https://api.boxtal.build/iam/account-app/token', {
            method: 'POST',
            headers: {
                'Authorization': 'Basic ' + btoa('UQL5NLC79MI03U32AJQJDV79ZZU5LOANYD57G21K:81b18827-16b5-4b2c-9cfc-6842cfd9be1a')
            }
        })
            .then(response => response.json())
            .then(data => {
                boxtalParcelPointMapRef.current = new BoxtalParcelPointMap({
                    domToLoadMap: '#parcel-point-map',
                    debug: true,
                    accessToken: data.accessToken,
                    baseUrl: 'https://maps.boxtal.build/app/v3/index.html',
                    config: {
                        locale: 'fr',
                        parcelPointNetworks: [
                            {
                                code: 'CHRP_NETWORK',
                                markerTemplate: {
                                    color: '#00d1b2'
                                },
                            }
                        ],
                        options: {
                            primaryColor: '#00d1b2',
                            autoSelectNearestParcelPoint: true
                        },
                    },
                });
            });
    }, []);

    const searchShipperParcelPoints = (data) => {
        if (boxtalParcelPointMapRef.current) {
            boxtalParcelPointMapRef.current.searchParcelPoints(
                {
                    country: data.country === "France" ? "FR" : "BE",
                    zipCode: data.zipCode,
                    city: data.city,
                    street: data.street,
                },
                (parcelPoint) => {
                    setParcelPoint(parcelPoint);
                }
            );
        }
    };

    const onSubmitAddress = handleSubmit((data) => {
        searchShipperParcelPoints(data);
    });

    const onSubmitFinal = handleSubmit((data) => {
        if (!parcelPoint) {
            return;
        }

        const finalData = {
            ...data,
            parcelPoint
        };
        // Traitement final des données
        setAdress(finalData);
        createCheckoutSession(finalData);
    });

    const createCheckoutSession = async (finalData) => {
        try {
            const response = await fetch('/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart: cart.map(item => ({
                        id: item.id,
                        name: item.title,
                        price: item.price,
                        quantity: item.quantitySelected
                    })),
                    adress: finalData
                })
            });

            if (!response.ok) {
                throw new Error('Erreur lors du checkout');
            }

            const checkoutSession = await response.json();

            // Gérer la réponse de Stripe ici
            setClientSecret(checkoutSession.client_secret);
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    return (
        <div className="container is-max-desktop">
            
            <form onSubmit={onSubmitAddress} className="box">
                <h2 className="title is-4">Adresse</h2>
                <div className="form-group field">
                    <label className="label">Pays</label>
                    <div className="select">
                    <select
                        {...register("country", {
                            required: "Le pays est requis"
                        })}
                    >
                        <option value="France">France</option>
                        <option value="Belgique">Belgique</option>
                    </select>
                    </div>
                    {errors.country && <span className="help is-danger">{errors.country.message}</span>}
                </div>

                <div className="form-group field">
                    <label className="label">Code postal</label>
                    <input
                        className="input"
                        {...register("zipCode", {
                            required: "Le code postal est requis",
                            pattern: {
                                value: /^\d{5}$/,
                                message: "Le code postal doit contenir 5 chiffres"
                            }
                        })}
                    />
                    {errors.zipCode && <span className="help is-danger">{errors.zipCode.message}</span>}
                </div>

                <div className="form-group field">
                    <label className="label">Ville</label>
                    <input
                        className="input"
                        {...register("city", {
                            required: "La ville est requise"
                        })}
                    />
                    {errors.city && <span className="help is-danger">{errors.city.message}</span>}
                </div>

                <div className="form-group field">
                    <label className="label">Rue</label>
                    <input
                        className="input"
                        {...register("street", {
                            required: "La rue est requise"
                        })}
                    />
                    {errors.street && <span className="help is-danger">{errors.street.message}</span>}
                </div>

                <button type="submit" className="button is-primary">Rechercher</button>
            </form>
                        
            <div className="box">
                <div id="parcel-point-map" className="has-background-white-ter"  style={{ height: '500px' }}></div>
            </div>


            {parcelPoint && (
                <>
                    
                    <form onSubmit={onSubmitFinal} className="box">
                        <h2 className="title is-4">Informations personnelles</h2>
                        <div className="form-group field">
                            <label className="label">Prénom</label>
                            <input
                                className="input"
                                {...register("firstName", {
                                    required: "Le prénom est requis"
                                })}
                            />
                            {errors.firstName && <span className="help is-danger">{errors.firstName.message}</span>}
                        </div>

                        <div className="form-group field">
                            <label className="label">Nom</label>
                            <input
                                className="input"
                                {...register("lastName", {
                                    required: "Le nom est requis"
                                })}
                            />
                            {errors.lastName && <span className="help is-danger">{errors.lastName.message}</span>}
                        </div>

                        <div className="form-group field">
                            <label className="label">Email</label>
                            <input
                                className="input"
                                type="email"
                                {...register("email", {
                                    required: "L'email est requis",
                                    pattern: {
                                        value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                                        message: "L'adresse email n'est pas valide"
                                    }
                                })}
                            />
                            {errors.email && <span className="help is-danger">{errors.email.message}</span>}
                        </div>

                        <div className="form-group field">
                            <label className="label">Téléphone</label>
                            <input
                                className="input"
                                type="tel"
                                {...register("phone", {
                                    required: "Le numéro de téléphone est requis",
                                    pattern: {
                                        value: /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/,
                                        message: "Le numéro de téléphone n'est pas valide"
                                    }
                                })}
                            />
                            {errors.phone && <span className="help is-danger">{errors.phone.message}</span>}
                        </div>

                        <button 
                            type="submit" 
                            className="button is-primary"
                            disabled={isSubmitting}
                        >
                            {isSubmitting ? 'Validation...' : 'Valider'}
                        </button>
                    </form>
                </>
            )}
        </div>
    );
}