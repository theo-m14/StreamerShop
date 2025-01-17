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
                                    color: '#94a1e8'
                                },
                            }
                        ],
                        options: {
                            primaryColor: '#00FA9A',
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
        <div>
            <h2>Adresse</h2>
            <form onSubmit={onSubmitAddress}>
                <div className="form-group">
                    <label>Pays</label>
                    <select
                        {...register("country", {
                            required: "Le pays est requis"
                        })}
                    >
                        <option value="France">France</option>
                        <option value="Belgique">Belgique</option>
                    </select>
                    {errors.country && <span className="error">{errors.country.message}</span>}
                </div>

                <div className="form-group">
                    <label>Code postal</label>
                    <input
                        {...register("zipCode", {
                            required: "Le code postal est requis",
                            pattern: {
                                value: /^\d{5}$/,
                                message: "Le code postal doit contenir 5 chiffres"
                            }
                        })}
                    />
                    {errors.zipCode && <span className="error">{errors.zipCode.message}</span>}
                </div>

                <div className="form-group">
                    <label>Ville</label>
                    <input
                        {...register("city", {
                            required: "La ville est requise"
                        })}
                    />
                    {errors.city && <span className="error">{errors.city.message}</span>}
                </div>

                <div className="form-group">
                    <label>Rue</label>
                    <input
                        {...register("street", {
                            required: "La rue est requise"
                        })}
                    />
                    {errors.street && <span className="error">{errors.street.message}</span>}
                </div>

                <button type="submit">Rechercher</button>
            </form>

            <div id="parcel-point-map" style={{ height: '500px' }}></div>

            {parcelPoint && (
                <>
                    <h2>Informations personnelles</h2>
                    <form onSubmit={onSubmitFinal}>
                        <div className="form-group">
                            <label>Prénom</label>
                            <input
                                {...register("firstName", {
                                    required: "Le prénom est requis"
                                })}
                            />
                            {errors.firstName && <span className="error">{errors.firstName.message}</span>}
                        </div>

                        <div className="form-group">
                            <label>Nom</label>
                            <input
                                {...register("lastName", {
                                    required: "Le nom est requis"
                                })}
                            />
                            {errors.lastName && <span className="error">{errors.lastName.message}</span>}
                        </div>

                        <div className="form-group">
                            <label>Email</label>
                            <input
                                type="email"
                                {...register("email", {
                                    required: "L'email est requis",
                                    pattern: {
                                        value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                                        message: "L'adresse email n'est pas valide"
                                    }
                                })}
                            />
                            {errors.email && <span className="error">{errors.email.message}</span>}
                        </div>

                        <div className="form-group">
                            <label>Téléphone</label>
                            <input
                                type="tel"
                                {...register("phone", {
                                    required: "Le numéro de téléphone est requis",
                                    pattern: {
                                        value: /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/,
                                        message: "Le numéro de téléphone n'est pas valide"
                                    }
                                })}
                            />
                            {errors.phone && <span className="error">{errors.phone.message}</span>}
                        </div>

                        <button 
                            type="submit" 
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