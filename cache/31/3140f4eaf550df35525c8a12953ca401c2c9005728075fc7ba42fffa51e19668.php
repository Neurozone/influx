<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* index.twig */
class __TwigTemplate_88560c604372cf9fefde5e121a0f2a57da50fa0a3091cfed1291c5155c11afa3 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        $this->loadTemplate("header.twig", "index.twig", 1)->display($context);
        // line 2
        echo "
<div id=\"helpPanel\" onclick=\"\$(this).fadeOut(200);\">
    <h3>Raccourcis clavier</h3>
    <ul>
        <li><strong>m</strong> marque l’&eacute;l&eacute;ment s&eacute;lectionn&eacute; comme lu / non lu</li>
        <li><strong>l</strong> marque l’&eacute;l&eacute;ment pr&eacute;c&eacute;dent comme non lu</li>
        <li><strong>s</strong> marque l’&eacute;l&eacute;ment s&eacute;lectionn&eacute; comme favori / non favori</li>
        <li><strong>n</strong> &eacute;l&eacute;ment suivant (sans l’ouvrir)</li>
        <li><strong>v</strong> ouvre l’URL de l’&eacute;l&eacute;ment s&eacute;lectionn&eacute;</li>
        <li><strong>p</strong> &eacute;l&eacute;ment pr&eacute;c&eacute;dent (sans l’ouvrir)</li>
        <li><strong>espace</strong> &eacute;l&eacute;ment suivant (et l’ouvrir)</li>
        <li><strong>k</strong> &eacute;l&eacute;ment pr&eacute;c&eacute;dent (et l’ouvrir)</li>
        <li><strong>o</strong> ou <strong>enter</strong> ouvrir l’&eacute;l&eacute;ment s&eacute;lectionn&eacute;</li>
        <li><strong>j</strong> change le mode d'affichage de l'article (titre, r&eacute;duit, complet)</li>
        <li>touche h pour afficher/masquer le panneau d’aide\"</li>
    </ul>
</div>

<div id=\"main\" class=\"wrapper clearfix index\">
    <!-- MENU -->
    <div id=\"menuBar\">
        <section class=\"searchMenu\">
            <form action=\"/search\" method=\"post\">
                <input name=\"search\" id=\"search\" placeholder=\"...\" value=\"\" type=\"text\">
                <button type=\"submit\">Rechercher</button>
            </form>
        </section>
        <aside>
            <!-- TITRE MENU + OPTION TOUT MARQUER COMME LU -->
            <h3 class=\"left\">Flux</h3>
            <button style=\"margin: 10px 0px 0px 10px;\"
                    onclick=\"if(confirm('Tout marquer comme lu pour tous les flux ?'))window.location='/action/read/all'\">
                Tout marquer comme lu
            </button>
            <div class=\"readingFolderButton\"
                 onclick=\"toggleUnreadFeedFolder(this, ";
        // line 37
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["config"] ?? null), "displayOnlyUnreadFeedFolder_reverse", [], "any", false, false, false, 37), "html", null, true);
        echo ") ;\"
                 title=\"Afficher uniquement non lus\"><i class=\"icon-resize-full\"></i></div>

            <ul class=\"fluxs clear\">

                <!--Pour chaques dossier-->
                ";
        // line 43
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["categories"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["category"]) {
            // line 44
            echo "
                    <!-- DOSSIER -->
                    <li>
                        <h1 class=\"category";
            // line 47
            if ((twig_get_attribute($this->env, $this->source, $context["category"], "unread", [], "any", false, false, false, 47) == 0)) {
                echo "hideflux";
            }
            echo "\" ";
            if (((twig_get_attribute($this->env, $this->source, ($context["config"] ?? null), "displayOnlyUnreadFeedFolder", [], "any", false, false, false, 47) == true) && (twig_get_attribute($this->env, $this->source, $context["category"], "unread", [], "any", false, false, false, 47) == 0))) {
                echo " style=\"display:none\"";
            }
            echo ">

                            <a title=\"Marquer comme lu\" class=\"categoryLink\"
                               href=\"/category/";
            // line 50
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["category"], "id", [], "any", false, false, false, 50), "html", null, true);
            echo "\"
                               data-id=\"";
            // line 51
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["category"], "id", [], "any", false, false, false, 51), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["category"], "name", [], "any", false, false, false, 51), "html", null, true);
            echo "</a>
                            <a class=\"readFolder\"
                               title=\"Plier/D&eacute;plier le dossier\"
                               onclick=\"toggleFolder(this,";
            // line 54
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["category"], "id", [], "any", false, false, false, 54), "html", null, true);
            echo " )\">
                                ";
            // line 55
            if ((twig_get_attribute($this->env, $this->source, $context["category"], "isopen", [], "any", false, false, false, 55) == 0)) {
                // line 56
                echo "                                    <i class=\"icon-category-empty\"></i>
                                ";
            } else {
                // line 58
                echo "                                    <i class=\"icon-category-open-empty\"></i>
                                ";
            }
            // line 59
            echo "</a>
                            ";
            // line 60
            if ((twig_get_attribute($this->env, $this->source, $context["category"], "unread", [], "any", false, false, false, 60) != 0)) {
                // line 61
                echo "                                <a class=\"unreadForFolder\"
                                   title=\"marquer comme lu le(s) ";
                // line 62
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["category"], "unread", [], "any", false, false, false, 62), "html", null, true);
                echo " evenement(s) non lu(s) de ce dossier\"
                                   data-mark-all-read=\"category\">";
                // line 63
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["category"], "unread", [], "any", false, false, false, 63), "html", null, true);
                echo "
                                    Non lu</a>
                            ";
            }
            // line 66
            echo "
                        </h1>
                        <!-- FLUX DU DOSSIER -->
                        <ul ";
            // line 69
            if ((twig_get_attribute($this->env, $this->source, $context["category"], "isopen", [], "any", false, false, false, 69) == 0)) {
                echo "style=\"display:none\" ";
            }
            echo ">
                            ";
            // line 70
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->source, $context["category"], "flux", [], "any", false, false, false, 70));
            foreach ($context['_seq'] as $context["_key"] => $context["flux"]) {
                // line 71
                echo "                                ";
                if ((twig_get_attribute($this->env, $this->source, ($context["config"] ?? null), "displayOnlyUnreadFeedFolder", [], "any", false, false, false, 71) == true)) {
                    // line 72
                    echo "                                    <!-- Affichage des fluxs ayant des articles non lus -->
                                    ";
                    // line 73
                    if ((twig_get_attribute($this->env, $this->source, $context["flux"], "unread", [], "any", false, false, false, 73) > 0)) {
                        // line 74
                        echo "                                        ";
                        if ((twig_get_attribute($this->env, $this->source, $context["flux"], "lastSyncInError", [], "any", false, false, false, 74) == 0)) {
                            // line 75
                            echo "                                            <li>
                                        ";
                        } else {
                            // line 77
                            echo "                                            <li class=\"errorSync\" title=\"Sync error\">
                                        ";
                        }
                        // line 79
                        echo "                                        <a
                                                href=\"/flux/";
                        // line 80
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "id", [], "any", false, false, false, 80), "html", null, true);
                        echo "\"
                                                class=\"fluxLink ";
                        // line 81
                        if ((twig_get_attribute($this->env, $this->source, $context["flux"], "id", [], "any", false, false, false, 81) == ($context["fluxId"] ?? null))) {
                            echo " selectedFlux";
                        }
                        echo "\"
                                                data-id=\"";
                        // line 82
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "id", [], "any", false, false, false, 82), "html", null, true);
                        echo "\"
                                                title=\"";
                        // line 83
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "url", [], "any", false, false, false, 83), "html", null, true);
                        echo "\">";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "name", [], "any", false, false, false, 83), "html", null, true);
                        echo " </a>
                                        <button class=\"right unreadForFeed\" data-mark-all-read=\"flux\">
                                            <span title=\"Marquer comme lu\">";
                        // line 85
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "unread", [], "any", false, false, false, 85), "html", null, true);
                        echo "</span>
                                        </button>

                                        </li>
                                    ";
                    } else {
                        // line 90
                        echo "                                        <!-- On cache les fluxs n'ayant pas d'article non lus -->
                                        ";
                        // line 91
                        if ((twig_get_attribute($this->env, $this->source, $context["flux"], "lastSyncInError", [], "any", false, false, false, 91) == 0)) {
                            // line 92
                            echo "                                            <li class=\"hideflux\" style=\"display:none;\">
                                        ";
                        } else {
                            // line 94
                            echo "                                            <li class=\"hideflux errorSync\" style=\"display:none;\"
                                            title=\"sync error\">
                                        ";
                        }
                        // line 97
                        echo "                                        <a
                                                href=\"/flux/";
                        // line 98
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "id", [], "any", false, false, false, 98), "html", null, true);
                        echo "\"
                                                class=\"fluxLink\"
                                                data-id=\"";
                        // line 100
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "id", [], "any", false, false, false, 100), "html", null, true);
                        echo "\"
                                                title=\"";
                        // line 101
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "url", [], "any", false, false, false, 101), "html", null, true);
                        echo "\">";
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "name", [], "any", false, false, false, 101), "html", null, true);
                        echo " </a>
                                        ";
                        // line 102
                        if (twig_get_attribute($this->env, $this->source, $context["flux"], "unread", [], "any", true, true, false, 102)) {
                            // line 103
                            echo "                                            <button class=\"right unreadForFeed\" data-mark-all-read=\"flux\">
                                                <span title=\"marquer comme lu\">";
                            // line 104
                            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "unread", [], "any", false, false, false, 104), "html", null, true);
                            echo "</span>
                                            </button>
                                        ";
                        }
                        // line 107
                        echo "
                                        </li>
                                    ";
                    }
                    // line 110
                    echo "                                ";
                } else {
                    // line 111
                    echo "                                    <!-- Affichage de tous les fluxs -->
                                    ";
                    // line 112
                    if (twig_get_attribute($this->env, $this->source, $context["flux"], "unread", [], "any", true, true, false, 112)) {
                        // line 113
                        echo "                                        ";
                        if ((twig_get_attribute($this->env, $this->source, $context["flux"], "lastSyncInError", [], "any", false, false, false, 113) == 0)) {
                            // line 114
                            echo "                                            <li>
                                        ";
                        } else {
                            // line 116
                            echo "                                            <li class=\"errorSync\" title=\"Erreur de sync\">
                                        ";
                        }
                        // line 118
                        echo "                                    ";
                    } else {
                        // line 119
                        echo "                                        ";
                        if ((twig_get_attribute($this->env, $this->source, $context["flux"], "lastSyncInError", [], "any", false, false, false, 119) == 0)) {
                            // line 120
                            echo "                                            <li class=\"hideflux\">
                                        ";
                        } else {
                            // line 122
                            echo "                                            <li class=\"hideflux errorSync\" title=\"Erreur de sync\">
                                        ";
                        }
                        // line 124
                        echo "                                    ";
                    }
                    // line 125
                    echo "                                    <a
                                            href=\"/flux/";
                    // line 126
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "id", [], "any", false, false, false, 126), "html", null, true);
                    echo "\"
                                            class=\"fluxLink\"
                                            data-id=\"";
                    // line 128
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "id", [], "any", false, false, false, 128), "html", null, true);
                    echo "\"
                                            title=\"";
                    // line 129
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "url", [], "any", false, false, false, 129), "html", null, true);
                    echo "\">";
                    echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "name", [], "any", false, false, false, 129), "html", null, true);
                    echo " </a>

                                    ";
                    // line 131
                    if ( !twig_get_attribute($this->env, $this->source, $context["flux"], "unread", [], "any", true, true, false, 131)) {
                        // line 132
                        echo "                                        <button class=\"right unreadForFeed\" data-mark-all-read=\"flux\">
                                            <span title=\"Marquer comme lu\">";
                        // line 133
                        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["flux"], "unread", [], "any", false, false, false, 133), "html", null, true);
                        echo "</span>
                                        </button>
                                    ";
                    }
                    // line 136
                    echo "
                                    </li>
                                ";
                }
                // line 139
                echo "
                            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['flux'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 141
            echo "

                        </ul>
                    </li>
                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['category'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 146
        echo "            </ul>
        </aside>


    </div>

    <article>
        <header class=\"articleHead\">

            ";
        // line 155
        if ((($context["action"] ?? null) == "flux")) {
            // line 156
            echo "
                <h1 class=\"articleSection\"><a target=\"_blank\" rel=\"noopener noreferrer\" href=\"";
            // line 157
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["flux"] ?? null), "website", [], "any", false, false, false, 157), "html", null, true);
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["flux"] ?? null), "name", [], "any", false, false, false, 157), "html", null, true);
            echo "</a> :</h1>
                ";
            // line 158
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["flux"] ?? null), "description", [], "any", false, false, false, 158), "html", null, true);
            echo "
                <div class=\"clear\"></div>

                <a href=\"/flux/";
            // line 161
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["flux"] ?? null), "id", [], "any", false, false, false, 161), "html", null, true);
            echo "/page/";
            echo twig_escape_filter($this->env, ($context["page"] ?? null), "html", null, true);
            echo "/unread\">Non lu</a> |
                <a href=\"/flux/";
            // line 162
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["flux"] ?? null), "id", [], "any", false, false, false, 162), "html", null, true);
            echo "/page/";
            echo twig_escape_filter($this->env, ($context["page"] ?? null), "html", null, true);
            echo "/older\">Plus vieux</a> en premier
            ";
        }
        // line 164
        echo "
            ";
        // line 165
        if ((($context["action"] ?? null) == "category")) {
            // line 166
            echo "                <h1 class=\"articleSection\">Dossier : ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["category"] ?? null), "name", [], "any", false, false, false, 166), "html", null, true);
            echo "</h1>
                <p>Tous les &eacute;v&eacute;nements non lus pour le dossier ";
            // line 167
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["category"] ?? null), "name", [], "any", false, false, false, 167), "html", null, true);
            echo "</p>
            ";
        }
        // line 169
        echo "
            ";
        // line 170
        if ((($context["action"] ?? null) == "favorites")) {
            // line 171
            echo "
                <h1 class=\"articleSection\">Articles favoris <span id=\"nbarticle\">";
            // line 172
            echo twig_escape_filter($this->env, ($context["numberOfItem"] ?? null), "html", null, true);
            echo "</span>
                    }}</h1>
            ";
        }
        // line 175
        echo "

            ";
        // line 177
        if (((($context["action"] ?? null) == "unreadEvents") || (($context["action"] ?? null) == ""))) {
            // line 178
            echo "            ";
            $context["helpPanel"] = "#helpPanel";
            // line 179
            echo "        </header>
        <header class=\"articleHead\">
            <h1 class=\"articleSection\">Non lu(s) (<span id=\"nbarticle\">";
            // line 181
            echo twig_escape_filter($this->env, ($context["numberOfItem"] ?? null), "html", null, true);
            echo "</span>)</h1>
            <i title=\"Touche h pour afficher/masquer le panneau d’aide\" onclick=\"\$('#helpPanel').fadeIn(200);\"
               class=\"icon-keyboard right pointer\"></i>
            ";
        }
        // line 185
        echo "
            <div class=\"clear\"></div>
        </header>

        ";
        // line 189
        $this->loadTemplate("article.twig", "index.twig", 189)->display($context);
        // line 190
        echo "    </article>


</div>
";
        // line 194
        $this->loadTemplate("footer.twig", "index.twig", 194)->display($context);
        // line 195
        echo "
";
    }

    public function getTemplateName()
    {
        return "index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  458 => 195,  456 => 194,  450 => 190,  448 => 189,  442 => 185,  435 => 181,  431 => 179,  428 => 178,  426 => 177,  422 => 175,  416 => 172,  413 => 171,  411 => 170,  408 => 169,  403 => 167,  398 => 166,  396 => 165,  393 => 164,  386 => 162,  380 => 161,  374 => 158,  368 => 157,  365 => 156,  363 => 155,  352 => 146,  342 => 141,  335 => 139,  330 => 136,  324 => 133,  321 => 132,  319 => 131,  312 => 129,  308 => 128,  303 => 126,  300 => 125,  297 => 124,  293 => 122,  289 => 120,  286 => 119,  283 => 118,  279 => 116,  275 => 114,  272 => 113,  270 => 112,  267 => 111,  264 => 110,  259 => 107,  253 => 104,  250 => 103,  248 => 102,  242 => 101,  238 => 100,  233 => 98,  230 => 97,  225 => 94,  221 => 92,  219 => 91,  216 => 90,  208 => 85,  201 => 83,  197 => 82,  191 => 81,  187 => 80,  184 => 79,  180 => 77,  176 => 75,  173 => 74,  171 => 73,  168 => 72,  165 => 71,  161 => 70,  155 => 69,  150 => 66,  144 => 63,  140 => 62,  137 => 61,  135 => 60,  132 => 59,  128 => 58,  124 => 56,  122 => 55,  118 => 54,  110 => 51,  106 => 50,  94 => 47,  89 => 44,  85 => 43,  76 => 37,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% include 'header.twig' %}

<div id=\"helpPanel\" onclick=\"\$(this).fadeOut(200);\">
    <h3>Raccourcis clavier</h3>
    <ul>
        <li><strong>m</strong> marque l’&eacute;l&eacute;ment s&eacute;lectionn&eacute; comme lu / non lu</li>
        <li><strong>l</strong> marque l’&eacute;l&eacute;ment pr&eacute;c&eacute;dent comme non lu</li>
        <li><strong>s</strong> marque l’&eacute;l&eacute;ment s&eacute;lectionn&eacute; comme favori / non favori</li>
        <li><strong>n</strong> &eacute;l&eacute;ment suivant (sans l’ouvrir)</li>
        <li><strong>v</strong> ouvre l’URL de l’&eacute;l&eacute;ment s&eacute;lectionn&eacute;</li>
        <li><strong>p</strong> &eacute;l&eacute;ment pr&eacute;c&eacute;dent (sans l’ouvrir)</li>
        <li><strong>espace</strong> &eacute;l&eacute;ment suivant (et l’ouvrir)</li>
        <li><strong>k</strong> &eacute;l&eacute;ment pr&eacute;c&eacute;dent (et l’ouvrir)</li>
        <li><strong>o</strong> ou <strong>enter</strong> ouvrir l’&eacute;l&eacute;ment s&eacute;lectionn&eacute;</li>
        <li><strong>j</strong> change le mode d'affichage de l'article (titre, r&eacute;duit, complet)</li>
        <li>touche h pour afficher/masquer le panneau d’aide\"</li>
    </ul>
</div>

<div id=\"main\" class=\"wrapper clearfix index\">
    <!-- MENU -->
    <div id=\"menuBar\">
        <section class=\"searchMenu\">
            <form action=\"/search\" method=\"post\">
                <input name=\"search\" id=\"search\" placeholder=\"...\" value=\"\" type=\"text\">
                <button type=\"submit\">Rechercher</button>
            </form>
        </section>
        <aside>
            <!-- TITRE MENU + OPTION TOUT MARQUER COMME LU -->
            <h3 class=\"left\">Flux</h3>
            <button style=\"margin: 10px 0px 0px 10px;\"
                    onclick=\"if(confirm('Tout marquer comme lu pour tous les flux ?'))window.location='/action/read/all'\">
                Tout marquer comme lu
            </button>
            <div class=\"readingFolderButton\"
                 onclick=\"toggleUnreadFeedFolder(this, {{ config.displayOnlyUnreadFeedFolder_reverse }}) ;\"
                 title=\"Afficher uniquement non lus\"><i class=\"icon-resize-full\"></i></div>

            <ul class=\"fluxs clear\">

                <!--Pour chaques dossier-->
                {% for category in categories %}

                    <!-- DOSSIER -->
                    <li>
                        <h1 class=\"category{% if category.unread == 0 %}hideflux{% endif %}\" {% if (config.displayOnlyUnreadFeedFolder == true) and (category.unread == 0) %} style=\"display:none\"{% endif %}>

                            <a title=\"Marquer comme lu\" class=\"categoryLink\"
                               href=\"/category/{{ category.id }}\"
                               data-id=\"{{ category.id }}\">{{ category.name }}</a>
                            <a class=\"readFolder\"
                               title=\"Plier/D&eacute;plier le dossier\"
                               onclick=\"toggleFolder(this,{{ category.id }} )\">
                                {% if category.isopen == 0 %}
                                    <i class=\"icon-category-empty\"></i>
                                {% else %}
                                    <i class=\"icon-category-open-empty\"></i>
                                {% endif %}</a>
                            {% if category.unread != 0 %}
                                <a class=\"unreadForFolder\"
                                   title=\"marquer comme lu le(s) {{ category.unread }} evenement(s) non lu(s) de ce dossier\"
                                   data-mark-all-read=\"category\">{{ category.unread }}
                                    Non lu</a>
                            {% endif %}

                        </h1>
                        <!-- FLUX DU DOSSIER -->
                        <ul {% if category.isopen == 0 %}style=\"display:none\" {% endif %}>
                            {% for flux in category.flux %}
                                {% if config.displayOnlyUnreadFeedFolder == true %}
                                    <!-- Affichage des fluxs ayant des articles non lus -->
                                    {% if flux.unread > 0 %}
                                        {% if flux.lastSyncInError == 0 %}
                                            <li>
                                        {% else %}
                                            <li class=\"errorSync\" title=\"Sync error\">
                                        {% endif %}
                                        <a
                                                href=\"/flux/{{ flux.id }}\"
                                                class=\"fluxLink {% if flux.id == fluxId %} selectedFlux{% endif %}\"
                                                data-id=\"{{ flux.id }}\"
                                                title=\"{{ flux.url }}\">{{ flux.name }} </a>
                                        <button class=\"right unreadForFeed\" data-mark-all-read=\"flux\">
                                            <span title=\"Marquer comme lu\">{{ flux.unread }}</span>
                                        </button>

                                        </li>
                                    {% else %}
                                        <!-- On cache les fluxs n'ayant pas d'article non lus -->
                                        {% if flux.lastSyncInError == 0 %}
                                            <li class=\"hideflux\" style=\"display:none;\">
                                        {% else %}
                                            <li class=\"hideflux errorSync\" style=\"display:none;\"
                                            title=\"sync error\">
                                        {% endif %}
                                        <a
                                                href=\"/flux/{{ flux.id }}\"
                                                class=\"fluxLink\"
                                                data-id=\"{{ flux.id }}\"
                                                title=\"{{ flux.url }}\">{{ flux.name }} </a>
                                        {% if flux.unread is defined %}
                                            <button class=\"right unreadForFeed\" data-mark-all-read=\"flux\">
                                                <span title=\"marquer comme lu\">{{ flux.unread }}</span>
                                            </button>
                                        {% endif %}

                                        </li>
                                    {% endif %}
                                {% else %}
                                    <!-- Affichage de tous les fluxs -->
                                    {% if flux.unread is defined %}
                                        {% if flux.lastSyncInError == 0 %}
                                            <li>
                                        {% else %}
                                            <li class=\"errorSync\" title=\"Erreur de sync\">
                                        {% endif %}
                                    {% else %}
                                        {% if flux.lastSyncInError == 0 %}
                                            <li class=\"hideflux\">
                                        {% else %}
                                            <li class=\"hideflux errorSync\" title=\"Erreur de sync\">
                                        {% endif %}
                                    {% endif %}
                                    <a
                                            href=\"/flux/{{ flux.id }}\"
                                            class=\"fluxLink\"
                                            data-id=\"{{ flux.id }}\"
                                            title=\"{{ flux.url }}\">{{ flux.name }} </a>

                                    {% if flux.unread is not defined %}
                                        <button class=\"right unreadForFeed\" data-mark-all-read=\"flux\">
                                            <span title=\"Marquer comme lu\">{{ flux.unread }}</span>
                                        </button>
                                    {% endif %}

                                    </li>
                                {% endif %}

                            {% endfor %}


                        </ul>
                    </li>
                {% endfor %}
            </ul>
        </aside>


    </div>

    <article>
        <header class=\"articleHead\">

            {% if action == 'flux' %}

                <h1 class=\"articleSection\"><a target=\"_blank\" rel=\"noopener noreferrer\" href=\"{{ flux.website }}\">{{ flux.name }}</a> :</h1>
                {{ flux.description }}
                <div class=\"clear\"></div>

                <a href=\"/flux/{{ flux.id }}/page/{{ page }}/unread\">Non lu</a> |
                <a href=\"/flux/{{ flux.id }}/page/{{ page }}/older\">Plus vieux</a> en premier
            {% endif %}

            {% if action == 'category' %}
                <h1 class=\"articleSection\">Dossier : {{ category.name }}</h1>
                <p>Tous les &eacute;v&eacute;nements non lus pour le dossier {{ category.name }}</p>
            {% endif %}

            {% if action == 'favorites' %}

                <h1 class=\"articleSection\">Articles favoris <span id=\"nbarticle\">{{ numberOfItem }}</span>
                    }}</h1>
            {% endif %}


            {% if (action == 'unreadEvents') or (action == '') %}
            {% set helpPanel = \"#helpPanel\" %}
        </header>
        <header class=\"articleHead\">
            <h1 class=\"articleSection\">Non lu(s) (<span id=\"nbarticle\">{{ numberOfItem }}</span>)</h1>
            <i title=\"Touche h pour afficher/masquer le panneau d’aide\" onclick=\"\$('#helpPanel').fadeIn(200);\"
               class=\"icon-keyboard right pointer\"></i>
            {% endif %}

            <div class=\"clear\"></div>
        </header>

        {% include \"article.twig\" %}
    </article>


</div>
{% include \"footer.twig\" %}

", "index.twig", "/data/www/dev.neurozone.fr/templates/influx/index.twig");
    }
}
