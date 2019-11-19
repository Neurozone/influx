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

/* header.twig */
class __TwigTemplate_45533649478386c085c4f817bafcfde938b5e64f1ac83206176db7accbdd7900 extends \Twig\Template
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
        echo "<!doctype html>
<!--[if lt IE 7]> <html class=\"no-js lt-ie9 lt-ie8 lt-ie7\" lang=\"en\"> <![endif]-->
<!--[if IE 7]>    <html class=\"no-js lt-ie9 lt-ie8\" lang=\"en\"> <![endif]-->
<!--[if IE 8]>    <html class=\"no-js lt-ie9\" lang=\"en\"> <![endif]-->
<!--[if gt IE 8]><!--><html class=\"no-js\" lang=\"en\"><!--<![endif]-->
<head>
    <title>
        ";
        // line 8
        if ((isset($context["currentFeed"]) || array_key_exists("currentFeed", $context))) {
            // line 9
            echo "            ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["currentFeed"] ?? null), "getName", [], "any", false, false, false, 9), "html", null, true);
            echo "
        ";
        }
        // line 11
        echo "        ";
        if ((isset($context["currentFolder"]) || array_key_exists("currentFolder", $context))) {
            // line 12
            echo "            ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["currentFolder"] ?? null), "getName", [], "any", false, false, false, 12), "html", null, true);
            echo "
        ";
        }
        // line 13
        echo "InFlux
    </title>
    <meta charset=\"utf-8\">
    <meta name=\"referrer\" content=\"no-referrer\" />
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge,chrome=1\">
    <meta name=\"description\" content=\"Agr&eacute;gateur de flux RSS InFlux\">
    <meta name=\"author\" content=\"Hecate\">
    <meta name=\"viewport\" content=\"width=device-width\">
    <link rel=\"icon\" type=\"image/png\" href=\"/templates/influx/css/favicon.png\" />
    <link rel=\"stylesheet\" href=\"/templates/influx/css/style.css\">

</head>
<body>
    <div class=\"global-wrapper\">
        <!-- <!> Balise ayant double utilit&eacute; : sert de base a javascript pour connaitre l'action courante permet le retour en haut de page -->
        <a id=\"pageTopAnvil\"></a>
        <a id=\"pageTop\" class=\"hidden\">";
        // line 29
        echo twig_escape_filter($this->env, ($context["action"] ?? null), "html", null, true);
        echo "</a>
        <div id=\"header-container\">
            <header class=\"wrapper clearfix\">
                <h1 class=\"logo\" id=\"title\"><a href=\"/\">In<i>Flux</i></a></h1>
                <div class=\"loginBloc\"> - <span><a href=\"/settings/user\" class=\"\">Identifi&eacute; avec  ";
        // line 33
        echo twig_escape_filter($this->env, ($context["user"] ?? null), "html", null, true);
        echo "</a> </span><a href=\"/logout\" class=\"loginButton\">";
        echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, ($context["trans"] ?? null), "DISCONNECT", [], "any", false, false, false, 33), "html", null, true);
        echo "</a>
                    <div class=\"clear\"></div>
                </div>
                <nav>
                    <ul>
                        <li><a href=\"/\" title=\"Accueil\"><i class=\"icon-home\"></i></a></li>
                        <li><a href=\"/favorites\" title=\"Favoris\"><i class=\"icon-star-empty\"></i></a></li>
                        <li><a href=\"/settings\" title=\"Gestion\"><i class=\"icon-cog\"></i></a></li>
                        <li><a class=\"synchronizeButton\" title=\"Lancer une synchronisation manuelle\" onclick=\"synchronize('";
        // line 41
        echo twig_escape_filter($this->env, ($context["synchronisationCode"] ?? null), "html", null, true);
        echo "');\"><i class=\"icon-arrows-cw\"></i></a></li>

                    </ul>
                </nav>
            </header>
        </div>
    <div id=\"main-container\">
";
    }

    public function getTemplateName()
    {
        return "header.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  101 => 41,  88 => 33,  81 => 29,  63 => 13,  57 => 12,  54 => 11,  48 => 9,  46 => 8,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!doctype html>
<!--[if lt IE 7]> <html class=\"no-js lt-ie9 lt-ie8 lt-ie7\" lang=\"en\"> <![endif]-->
<!--[if IE 7]>    <html class=\"no-js lt-ie9 lt-ie8\" lang=\"en\"> <![endif]-->
<!--[if IE 8]>    <html class=\"no-js lt-ie9\" lang=\"en\"> <![endif]-->
<!--[if gt IE 8]><!--><html class=\"no-js\" lang=\"en\"><!--<![endif]-->
<head>
    <title>
        {% if currentFeed is defined %}
            {{ attribute(currentFeed, 'getName') }}
        {% endif %}
        {% if currentFolder is defined %}
            {{ currentFolder.getName }}
        {% endif %}InFlux
    </title>
    <meta charset=\"utf-8\">
    <meta name=\"referrer\" content=\"no-referrer\" />
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge,chrome=1\">
    <meta name=\"description\" content=\"Agr&eacute;gateur de flux RSS InFlux\">
    <meta name=\"author\" content=\"Hecate\">
    <meta name=\"viewport\" content=\"width=device-width\">
    <link rel=\"icon\" type=\"image/png\" href=\"/templates/influx/css/favicon.png\" />
    <link rel=\"stylesheet\" href=\"/templates/influx/css/style.css\">

</head>
<body>
    <div class=\"global-wrapper\">
        <!-- <!> Balise ayant double utilit&eacute; : sert de base a javascript pour connaitre l'action courante permet le retour en haut de page -->
        <a id=\"pageTopAnvil\"></a>
        <a id=\"pageTop\" class=\"hidden\">{{ action }}</a>
        <div id=\"header-container\">
            <header class=\"wrapper clearfix\">
                <h1 class=\"logo\" id=\"title\"><a href=\"/\">In<i>Flux</i></a></h1>
                <div class=\"loginBloc\"> - <span><a href=\"/settings/user\" class=\"\">Identifi&eacute; avec  {{ user }}</a> </span><a href=\"/logout\" class=\"loginButton\">{{ trans.DISCONNECT }}</a>
                    <div class=\"clear\"></div>
                </div>
                <nav>
                    <ul>
                        <li><a href=\"/\" title=\"Accueil\"><i class=\"icon-home\"></i></a></li>
                        <li><a href=\"/favorites\" title=\"Favoris\"><i class=\"icon-star-empty\"></i></a></li>
                        <li><a href=\"/settings\" title=\"Gestion\"><i class=\"icon-cog\"></i></a></li>
                        <li><a class=\"synchronizeButton\" title=\"Lancer une synchronisation manuelle\" onclick=\"synchronize('{{ synchronisationCode }}');\"><i class=\"icon-arrows-cw\"></i></a></li>

                    </ul>
                </nav>
            </header>
        </div>
    <div id=\"main-container\">
", "header.twig", "/data/www/dev.neurozone.fr/templates/influx/header.twig");
    }
}
