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

/* footer.twig */
class __TwigTemplate_ebc3d370b4571c0cfad424bbdcf7057d9132b0864d45b8bed0607b1069b961d3 extends \Twig\Template
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
        echo "
    </div> <!-- #main-container -->

</div>

    <footer></footer>

<script src=\"/templates/influx/js/libs/jqueryAndModernizr.min.js\"></script>
<script src=\"/templates/influx/js/script.js\"></script>

</body>
</html>
";
    }

    public function getTemplateName()
    {
        return "footer.twig";
    }

    public function getDebugInfo()
    {
        return array (  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("
    </div> <!-- #main-container -->

</div>

    <footer></footer>

<script src=\"/templates/influx/js/libs/jqueryAndModernizr.min.js\"></script>
<script src=\"/templates/influx/js/script.js\"></script>

</body>
</html>
", "footer.twig", "/data/www/dev.neurozone.fr/templates/influx/footer.twig");
    }
}
