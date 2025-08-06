<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card';
import GlobalLayout from '@/layouts/GlobalLayout.vue';
import { Icon } from '@iconify/vue';
import { onMounted, ref } from 'vue';

// Animation de la timeline
const timelineRef = ref<HTMLElement>();
const timelineProgress = ref(0);

onMounted(() => {
    const handleScroll = () => {
        if (!timelineRef.value) return;

        const timelineElement = timelineRef.value;
        const rect = timelineElement.getBoundingClientRect();
        const windowHeight = window.innerHeight;

        // Calculer le pourcentage de progression basé sur le scroll
        const elementTop = rect.top;
        const elementHeight = rect.height;

        // Commencer l'animation quand l'élément entre dans la vue
        if (elementTop < windowHeight && elementTop + elementHeight > 0) {
            const visibleHeight = Math.min(windowHeight - elementTop, elementHeight);
            const progress = Math.max(0, Math.min(100, (visibleHeight / elementHeight) * 100));
            timelineProgress.value = progress;
        }
    };

    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Appel initial

    return () => {
        window.removeEventListener('scroll', handleScroll);
    };
});
</script>

<template>
    <GlobalLayout>
        <!-- Hero Section -->
        <section class="bg-secondary py-20">
            <div class="mx-auto max-w-6xl px-4 text-center">
                <div class="bg-primary-opacity text-primary mb-6 inline-block rounded-full px-4 py-2 text-sm font-medium">Notre histoire</div>
                <h1 class="mb-6 text-4xl leading-tight font-bold text-gray-900 md:text-5xl">
                    Derrière la plateforme, une mission simple : <br />
                    <span class="text-primary">faciliter la garde d'enfants</span> partout en France
                </h1>
                <p class="mx-auto max-w-4xl text-lg leading-relaxed text-gray-600">
                    Chez Trouve ta Babysitter, on croit qu'il ne devrait pas être compliqué de trouver une personne de confiance pour garder ses
                    enfants. C'est pourquoi on a imaginé une plateforme simple, rapide, et surtout humaine.
                </p>
            </div>
        </section>

        <!-- Section Histoire -->
        <section class="bg-white py-20">
            <div class="mx-auto max-w-6xl px-4">
                <div class="grid items-center gap-16 md:grid-cols-2">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold text-gray-900">Une idée née autour d'un dîner... et d'une galère</h2>
                        <div class="space-y-6 text-gray-600">
                            <p>
                                C'était un soir d'été, autour d'un plat de pâtes, que tout a commencé. Manon, habituée à faire du babysitting depuis
                                des années, partageait avec son ami Rémi, développeur web, à quel point il était parfois compliqué de trouver
                                rapidement des missions ou, pour les parents, de dénicher une babysitter fiable à la dernière minute. La conversation
                                dévie sur les galères vécues par leurs proches : babysitters qui annulent au dernier moment, parents qui appellent en
                                urgence, soirées annulées faute de solution… Bref, un problème bien réel.
                            </p>
                            <p>C’est là que Rémi lance, mi-sérieux, mi-amusé :</p>
                            <blockquote class="border-primary text-primary border-l-4 pl-4 italic">
                                « Et si on créait une appli pour trouver une babysitter fiable en 15 minutes chrono ? »
                            </blockquote>
                            <p>L’idée fait sourire… mais ne les quitte plus.</p>
                        </div>
                    </div>
                    <div class="relative">
                        <img src="/storage/equipe-trouve-ta-babysitter.png" alt="Equipe Trouve ta Babysitter" class="w-full rounded-2xl" />
                    </div>
                </div>

                <!-- Le déclic -->
                <div class="bg-secondary mt-20 rounded-2xl p-8 md:p-12">
                    <h3 class="mb-6 text-2xl font-bold text-gray-900">Le déclic</h3>
                    <div class="leading-relaxed text-gray-600">
                        <p>
                            Quelques jours plus tard, ils commencent à en parler autour d'eux. Et là, surprise : les parents ET les babysitters
                            galèrent. Entre les groupes Facebook pas toujours rassurants, les plateformes floues avec un abonnement, ou les appels de
                            dernière minute... personne n'est vraiment satisfait.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Équipe -->
        <section class="bg-secondary py-20">
            <div class="mx-auto max-w-6xl px-4">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">Le début de Trouve ta Babysitter</h2>
                </div>

                <div class="mb-16 grid gap-8 md:grid-cols-3">
                    <!-- Julie -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 h-24 w-24 overflow-hidden rounded-full">
                            <img src="/storage/manon-trouve-ta-babysitter.png" alt="Julie" class="h-full w-full object-cover" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">Manon</h3>
                        <p class="text-primary mb-2 font-medium">Co-fondatrice</p>
                        <p class="text-sm text-gray-600">Product Design & experte UX, elle comprend les besoins des familles</p>
                    </div>

                    <!-- Lucas -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 h-24 w-24 overflow-hidden rounded-full">
                            <img src="/storage/remi-trouve-ta-babysitter.png" alt="Lucas" class="h-full w-full object-cover" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">Rémi</h3>
                        <p class="text-primary mb-2 font-medium">Co-fondateur & CTO</p>
                        <p class="text-sm text-gray-600">Développeur passionné, il donne vie à la plateforme</p>
                    </div>

                    <!-- Sarah -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 h-24 w-24 overflow-hidden rounded-full">
                            <img src="/storage/jade-trouve-ta-babysitter.png" alt="Sarah" class="h-full w-full object-cover" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">Jade</h3>
                        <p class="text-primary mb-2 font-medium">Responsable Communauté</p>
                        <p class="text-sm text-gray-600">Éducatrice de formation, elle veille à la qualité des profils</p>
                    </div>
                </div>

                <div class="mx-auto max-w-4xl text-center">
                    <p class="leading-relaxed text-gray-600">
                        Avec Jade, une amie éducatrice passionnée par la petite enfance, ils se lancent : créer une plateforme simple, humaine et
                        efficace pour mettre en relation les familles et des babysitters de confiance. Ils bossent le soir, les week-ends, entre deux
                        couches ou deux réunions. Et après plusieurs mois de travail acharné, la plateforme voit le jour.
                    </p>
                </div>
            </div>
        </section>

        <!-- Section Valeurs -->
        <section class="bg-white py-20">
            <div class="mx-auto max-w-6xl px-4">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">Nos valeurs</h2>
                    <p class="text-gray-600">Ce qui nous guide au quotidien pour créer la meilleure expérience possible</p>
                </div>

                <div class="grid gap-8 md:grid-cols-4">
                    <!-- Confiance -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                            <Icon icon="lucide:shield-check" class="text-primary h-8 w-8" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">Confiance</h3>
                        <p class="text-sm text-gray-600">Profils vérifiés, avis authentiques et processus de sélection rigoureux</p>
                    </div>

                    <!-- Rapidité -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                            <Icon icon="lucide:clock" class="text-primary h-8 w-8" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">Rapidité</h3>
                        <p class="text-sm text-gray-600">Trouvez une babysitter en quelques minutes, même en urgence</p>
                    </div>

                    <!-- Liberté -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                            <Icon icon="lucide:user" class="text-primary h-8 w-8" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">Liberté</h3>
                        <p class="text-sm text-gray-600">Chaque babysitter choisit ses disponibilités et ses conditions</p>
                    </div>

                    <!-- Bienveillance -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                            <Icon icon="lucide:heart" class="text-primary h-8 w-8" />
                        </div>
                        <h3 class="mb-2 text-xl font-bold text-gray-900">Bienveillance</h3>
                        <p class="text-sm text-gray-600">Une communauté respectueuse qui met l'humain au centre</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Timeline -->
        <section class="bg-secondary py-20" ref="timelineRef">
            <div class="mx-auto max-w-4xl px-4">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">Notre parcours</h2>
                    <p class="text-gray-600">De l'idée à la réalité, retour sur les étapes clés de Trouve ta Babysitter</p>
                </div>

                <div class="relative">
                    <!-- Ligne de timeline animée -->
                    <div class="absolute left-1/2 h-full w-1 -translate-x-1/2 transform bg-gray-200">
                        <div class="bg-primary w-full transition-all duration-500 ease-out" :style="{ height: `${timelineProgress}%` }"></div>
                    </div>

                    <!-- Événements timeline -->
                    <div class="space-y-16">
                        <!-- 2023 - L'idée naît -->
                        <div class="relative flex items-center">
                            <div class="flex-1 pr-8 text-right">
                                <Card class="bg-white shadow-lg">
                                    <CardContent class="p-6">
                                        <div class="text-primary mb-2 text-sm font-bold">2023</div>
                                        <h3 class="mb-2 text-xl font-bold text-gray-900">L'idée naît</h3>
                                        <p class="text-gray-600">Autour d'un dîner, face à une énième galère de babysitting</p>
                                    </CardContent>
                                </Card>
                            </div>
                            <div
                                class="bg-primary absolute left-1/2 z-10 h-4 w-4 -translate-x-1/2 transform rounded-full border-4 border-white shadow-lg"
                            ></div>
                            <div class="flex-1 pl-8"></div>
                        </div>

                        <!-- 2024 - Développement -->
                        <div class="relative flex items-center">
                            <div class="flex-1 pr-8"></div>
                            <div
                                class="bg-primary absolute left-1/2 z-10 h-4 w-4 -translate-x-1/2 transform rounded-full border-4 border-white shadow-lg"
                            ></div>
                            <div class="flex-1 pl-8">
                                <Card class="bg-white shadow-lg">
                                    <CardContent class="p-6">
                                        <div class="text-primary mb-2 text-sm font-bold">2024</div>
                                        <h3 class="mb-2 text-xl font-bold text-gray-900">Développement</h3>
                                        <p class="text-gray-600">Création de la plateforme avec une équipe passionnée</p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- Aujourd'hui - Communauté grandissante -->
                        <div class="relative flex items-center">
                            <div class="flex-1 pr-8 text-right">
                                <Card class="bg-white shadow-lg">
                                    <CardContent class="p-6">
                                        <div class="text-primary mb-2 text-sm font-bold">Aujourd'hui</div>
                                        <h3 class="mb-2 text-xl font-bold text-gray-900">Communauté grandissante</h3>
                                        <p class="text-gray-600">Des centaines de familles et babysitters nous font confiance</p>
                                    </CardContent>
                                </Card>
                            </div>
                            <div
                                class="bg-primary absolute left-1/2 z-10 h-4 w-4 -translate-x-1/2 transform rounded-full border-4 border-white shadow-lg"
                            ></div>
                            <div class="flex-1 pl-8"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Statistiques -->
        <section class="bg-primary py-20 text-white">
            <div class="mx-auto max-w-6xl px-4">
                <div class="mb-12 text-center">
                    <h2 class="mb-4 text-3xl font-bold">Aujourd'hui, trouve ta Babysitter c'est...</h2>
                    <p class="text-orange-100">Une communauté bienveillante qui grandit chaque jour</p>
                </div>

                <div class="grid gap-8 text-center md:grid-cols-4">
                    <div>
                        <div class="mb-2 text-4xl font-bold">200+</div>
                        <div class="text-orange-100">Familles satisfaites</div>
                    </div>
                    <div>
                        <div class="mb-2 text-4xl font-bold">700+</div>
                        <div class="text-orange-100">Babysitters vérifiées</div>
                    </div>
                    <div>
                        <div class="mb-2 text-4xl font-bold">400+</div>
                        <div class="text-orange-100">Gardes réalisées</div>
                    </div>
                    <div>
                        <div class="mb-2 text-4xl font-bold">4.9/5</div>
                        <div class="text-orange-100">Note moyenne</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Mission -->
        <section class="bg-white py-20">
            <div class="mx-auto max-w-6xl px-4">
                <div class="mb-16 text-center">
                    <h2 class="mb-4 text-3xl font-bold text-gray-900">Notre mission</h2>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <!-- Créer une plateforme de confiance -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                            <Icon icon="lucide:shield" class="text-primary h-8 w-8" />
                        </div>
                        <h3 class="mb-4 text-xl font-bold text-gray-900">Créer une plateforme de confiance</h3>
                        <p class="text-gray-600">
                            Qui respecte les familles comme les babysitters, avec des profils vérifiés et des avis authentiques.
                        </p>
                    </div>

                    <!-- Simplifier la vie de tous -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
                            <Icon icon="lucide:lightbulb" class="h-8 w-8 text-blue-600" />
                        </div>
                        <h3 class="mb-4 text-xl font-bold text-gray-900">Simplifier la vie de tous</h3>
                        <p class="text-gray-600">Avec un outil clair, mobile- first et sans prise de tête, accessible à tous.</p>
                    </div>

                    <!-- Mettre l'humain au centre -->
                    <div class="text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                            <Icon icon="lucide:heart" class="h-8 w-8 text-green-600" />
                        </div>
                        <h3 class="mb-4 text-xl font-bold text-gray-900">Mettre l'humain au centre</h3>
                        <p class="text-gray-600">Dans un monde qui va souvent trop vite, nous privilégions les relations humaines.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section CTA Final -->
        <section class="bg-secondary py-20">
            <div class="mx-auto max-w-4xl px-4 text-center">
                <div class="bg-primary-opacity text-primary mb-6 inline-flex items-center rounded-full px-4 py-2">
                    <Icon icon="lucide:heart" class="mr-2 h-5 w-5" />
                    <span class="text-sm font-medium">Rejoignez l'aventure</span>
                </div>

                <h2 class="mb-6 text-4xl font-bold text-gray-900">Bienvenue chez Trouvez ta Babysitter 🧡</h2>

                <div class="mb-8 space-y-2">
                    <p class="text-lg text-gray-600">Si vous êtes babysitter, il est temps de valoriser votre expérience.</p>
                    <p class="text-lg text-gray-600">Si vous êtes parent, on est là pour vous aider à souffler un peu.</p>
                </div>

                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="/inscription" class="bg-primary rounded-lg px-8 py-3 font-medium text-white transition-colors hover:bg-orange-600">
                        Devenir babysitter →
                    </a>
                    <a
                        href="/annonces"
                        class="border-primary text-primary rounded-lg border-2 px-8 py-3 font-medium transition-colors hover:bg-orange-50"
                    >
                        Trouver une babysitter
                    </a>
                </div>
            </div>
        </section>
    </GlobalLayout>
</template>
