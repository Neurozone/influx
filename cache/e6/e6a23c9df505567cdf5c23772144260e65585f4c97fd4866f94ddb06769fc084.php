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

/* article.twig */
class __TwigTemplate_3fcef08d1d66dc1dd668176bf60982e6c7fb1cab87306a81e95dd63426438382 extends \Twig\Template
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
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["events"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["event"]) {
            // line 2
            echo "    ";
            $context["plainDescription"] = twig_escape_filter($this->env, strip_tags(twig_get_attribute($this->env, $this->source, $context["event"], "description", [], "any", false, false, false, 2)));
            // line 3
            echo "
    <!-- CORPS ARTICLE -->
    <section id=\"";
            // line 5
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 5), "html", null, true);
            echo "\" data-feed=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "flux", [], "any", false, false, false, 5), "html", null, true);
            echo "\" class=\"";
            if ((twig_get_attribute($this->env, $this->source, $context["event"], "unread", [], "any", false, false, false, 5) == 0)) {
                echo "eventRead";
            } else {
                echo "eventUnread eventSelected";
            }
            echo " ";
            if ((0 == ($context["hightlighted"] ?? null) % 2)) {
                echo "eventHightLighted";
            }
            echo "\">
        <a title=\"Retourner au d&eacute;but\" class=\"goTopButton\" href=\"#pageTopAnvil\"><i class=\"icon-up-dir\"></i></a>
        <!-- TITRE -->
        <h2 class=\"articleTitle\">
            <a onclick=\"readThis(this,'";
            // line 9
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 9), "html", null, true);
            echo "','title');\" target=\"_blank\" rel=\"noopener noreferrer\" href=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "link", [], "any", false, false, false, 9), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, ($context["plainDescription"] ?? null));
            echo "\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "title", [], "any", false, false, false, 9));
            echo "</a>
        </h2>
        <!-- DETAILS + OPTIONS -->
        <h3 class=\"articleDetails\">
                <a href=\"";
            // line 13
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "link", [], "any", false, false, false, 13), "html", null, true);
            echo "\" target=\"_blank\" rel=\"noopener noreferrer\">";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "flux_name", [], "any", false, false, false, 13), "html", null, true);
            echo "</a>

                ";
            // line 15
            if (twig_get_attribute($this->env, $this->source, $context["event"], "creator", [], "any", false, false, false, 15)) {
                // line 16
                echo "                    par ";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "creator", [], "any", false, false, false, 16), "html", null, true);
                echo "
                ";
            }
            // line 18
            echo "
                ";
            // line 19
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "pubdate", [], "any", false, false, false, 19), "html", null, true);
            echo "

            ";
            // line 21
            if ((twig_get_attribute($this->env, $this->source, $context["event"], "getFavorite", [], "any", false, false, false, 21) != 1)) {
                echo " -  <a class=\"pointer favorite\" onclick=\"addFavorite(this,";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 21), "html", null, true);
                echo ");\" >Favoriser</a>
            ";
            } else {
                // line 23
                echo "            <a class=\"pointer favorite\" onclick=\"removeFavorite(this,";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 23), "html", null, true);
                echo ");\" >Supprimer des favoris</a>
            ";
            }
            // line 25
            echo "            <a class=\"pointer\" id=\"btnDisplayMode_";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 25), "html", null, true);
            echo "\" onclick=\"toggleArticleDisplayMode(this,";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 25), "html", null, true);
            echo ")\" title=\"Affichage mode complet\">|||</a>

            <a class=\"pointer right readUnreadButton\" onclick=\"readThis(this,";
            // line 27
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 27), "html", null, true);
            echo ",'item');\"><i class=\"icon-eye\" style=\"font-size:15px;\"></i> Lu/Non lu</a>

        </h3>

        <!-- CONTENU/DESCRIPTION -->

            <div class=\"articleContent summary\" style=\"display: none;\"></div>
            <div class=\"articleContent content\">

            ";
            // line 36
            echo twig_get_attribute($this->env, $this->source, $context["event"], "content", [], "any", false, false, false, 36);
            echo "

            </div>

        <!-- RAPPEL DETAILS + OPTIONS POUR LES ARTICLES AFFICHES EN ENTIER -->
        <h3 class=\"articleDetails\">

            <a class=\"pointer right readUnreadButton\"><i class=\"icon-eye\" style=\"font-size:15px;\"></i> Lu/Non lu</a>
            ";
            // line 44
            if ((twig_get_attribute($this->env, $this->source, $context["event"], "getFavorite", [], "any", false, false, false, 44) != 1)) {
                // line 45
                echo "            <a class=\"right pointer favorite\"  onclick=\"addFavorite(this,";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 45), "html", null, true);
                echo ");\">Ajouter aux favoris</a>
            ";
            } else {
                // line 47
                echo "            <a class=\"right pointer favorite\" onclick=\"removeFavorite(this,";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["event"], "id", [], "any", false, false, false, 47), "html", null, true);
                echo ");\">Supprimer des favoris</a>
            ";
            }
            // line 49
            echo "            <div class=\"clear\"></div>

        </h3>
    </section>

    ";
            // line 54
            $context["hightlighted"] = (($context["hightlighted"] ?? null) + 1);
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['event'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 56
        if (((isset($context["scroll"]) || array_key_exists("scroll", $context)) && (isset($context["events"]) || array_key_exists("events", $context)))) {
            // line 57
            echo "            <div class='scriptaddbutton'><script>addEventsButtonLuNonLus;</script></div>
";
        }
    }

    public function getTemplateName()
    {
        return "article.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  176 => 57,  174 => 56,  168 => 54,  161 => 49,  155 => 47,  149 => 45,  147 => 44,  136 => 36,  124 => 27,  116 => 25,  110 => 23,  103 => 21,  98 => 19,  95 => 18,  89 => 16,  87 => 15,  80 => 13,  67 => 9,  48 => 5,  44 => 3,  41 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("{% for event in events %}
    {% set plainDescription = event.description|striptags|escape %}

    <!-- CORPS ARTICLE -->
    <section id=\"{{ event.id }}\" data-feed=\"{{ event.flux }}\" class=\"{% if event.unread == 0 %}eventRead{% else %}eventUnread eventSelected{% endif %} {% if hightlighted is divisible by(2) %}eventHightLighted{% endif %}\">
        <a title=\"Retourner au d&eacute;but\" class=\"goTopButton\" href=\"#pageTopAnvil\"><i class=\"icon-up-dir\"></i></a>
        <!-- TITRE -->
        <h2 class=\"articleTitle\">
            <a onclick=\"readThis(this,'{{ event.id }}','title');\" target=\"_blank\" rel=\"noopener noreferrer\" href=\"{{ event.link }}\" title=\"{{ plainDescription|escape }}\">{{ event.title|escape }}</a>
        </h2>
        <!-- DETAILS + OPTIONS -->
        <h3 class=\"articleDetails\">
                <a href=\"{{ event.link }}\" target=\"_blank\" rel=\"noopener noreferrer\">{{  event.flux_name }}</a>

                {% if event.creator %}
                    par {{ event.creator }}
                {% endif %}

                {{ event.pubdate }}

            {% if event.getFavorite != 1 %} -  <a class=\"pointer favorite\" onclick=\"addFavorite(this,{{ event.id }});\" >Favoriser</a>
            {% else %}
            <a class=\"pointer favorite\" onclick=\"removeFavorite(this,{{ event.id }});\" >Supprimer des favoris</a>
            {% endif %}
            <a class=\"pointer\" id=\"btnDisplayMode_{{ event.id }}\" onclick=\"toggleArticleDisplayMode(this,{{ event.id }})\" title=\"Affichage mode complet\">|||</a>

            <a class=\"pointer right readUnreadButton\" onclick=\"readThis(this,{{ event.id }},'item');\"><i class=\"icon-eye\" style=\"font-size:15px;\"></i> Lu/Non lu</a>

        </h3>

        <!-- CONTENU/DESCRIPTION -->

            <div class=\"articleContent summary\" style=\"display: none;\"></div>
            <div class=\"articleContent content\">

            {{ event.content|raw }}

            </div>

        <!-- RAPPEL DETAILS + OPTIONS POUR LES ARTICLES AFFICHES EN ENTIER -->
        <h3 class=\"articleDetails\">

            <a class=\"pointer right readUnreadButton\"><i class=\"icon-eye\" style=\"font-size:15px;\"></i> Lu/Non lu</a>
            {% if event.getFavorite != 1 %}
            <a class=\"right pointer favorite\"  onclick=\"addFavorite(this,{{ event.id }});\">Ajouter aux favoris</a>
            {% else %}
            <a class=\"right pointer favorite\" onclick=\"removeFavorite(this,{{ event.id }});\">Supprimer des favoris</a>
            {% endif %}
            <div class=\"clear\"></div>

        </h3>
    </section>

    {% set hightlighted = hightlighted + 1 %}
{% endfor %}
{% if (scroll is defined) and (events is defined) %}
            <div class='scriptaddbutton'><script>addEventsButtonLuNonLus;</script></div>
{% endif %}
", "article.twig", "/data/www/dev.neurozone.fr/templates/influx/article.twig");
    }
}
