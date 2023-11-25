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

/* database/operations/index.twig */
class __TwigTemplate_66ad626f053178913b041e039f33d640 extends Template
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
        echo "<div class=\"container-fluid\">

  ";
        // line 3
        echo ($context["message"] ?? null);
        echo "

  ";
        // line 5
        if (($context["has_comment"] ?? null)) {
            // line 6
            echo "    <form method=\"post\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/operations");
            echo "\" id=\"formDatabaseComment\">
      ";
            // line 7
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
            echo "
      <div class=\"card mb-2\">
        <div class=\"card-header\">";
            // line 9
            echo PhpMyAdmin\Html\Generator::getIcon("b_comment", _gettext("Database comment"), true);
            echo "</div>
        <div class=\"card-body\">
          <div class=\"row g-3\">
            <div class=\"col-auto\">
              <label class=\"visually-hidden\" for=\"databaseCommentInput\">";
echo _gettext("Database comment");
            // line 13
            echo "</label>
              <input class=\"form-control textfield\" id=\"databaseCommentInput\" type=\"text\" name=\"comment\" value=\"";
            // line 14
            echo twig_escape_filter($this->env, ($context["db_comment"] ?? null), "html", null, true);
            echo "\">
            </div>
          </div>
        </div>
        <div class=\"card-footer text-end\">
          <input class=\"btn btn-primary\" type=\"submit\" value=\"";
echo _gettext("Go");
            // line 19
            echo "\">
        </div>
      </div>
    </form>
  ";
        }
        // line 24
        echo "
  <form id=\"createTableMinimalForm\" method=\"post\" action=\"";
        // line 25
        echo PhpMyAdmin\Url::getFromRoute("/table/create");
        echo "\" class=\"card mb-2 lock-page\">
    ";
        // line 26
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        echo "
    <div class=\"card-header\">";
        // line 27
        echo PhpMyAdmin\Html\Generator::getIcon("b_table_add", _gettext("Create new table"), true);
        echo "</div>
    <div class=\"card-body row row-cols-lg-auto g-3\">
      <div class=\"col-md-6\">
        <label for=\"createTableNameInput\" class=\"form-label\">";
echo _gettext("Table name");
        // line 30
        echo "</label>
        <input type=\"text\" class=\"form-control\" name=\"table\" id=\"createTableNameInput\" maxlength=\"64\" required>
      </div>
      <div class=\"col-md-6\">
        <label for=\"createTableNumFieldsInput\" class=\"form-label\">";
echo _gettext("Number of columns");
        // line 34
        echo "</label>
        <input type=\"number\" class=\"form-control\" name=\"num_fields\" id=\"createTableNumFieldsInput\" min=\"1\" value=\"4\" required>
      </div>
    </div>
    <div class=\"card-footer text-end\">
      <input class=\"btn btn-primary\" type=\"submit\" value=\"";
echo _gettext("Create");
        // line 39
        echo "\">
    </div>
  </form>

  ";
        // line 43
        if ((($context["db"] ?? null) != "mysql")) {
            // line 44
            echo "    <form id=\"rename_db_form\" class=\"ajax\" method=\"post\" action=\"";
            echo PhpMyAdmin\Url::getFromRoute("/database/operations");
            echo "\">
      ";
            // line 45
            echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
            echo "
      <input type=\"hidden\" name=\"what\" value=\"data\">
      <input type=\"hidden\" name=\"db_rename\" value=\"true\">

      ";
            // line 49
            if ( !twig_test_empty(($context["db_collation"] ?? null))) {
                // line 50
                echo "        <input type=\"hidden\" name=\"db_collation\" value=\"";
                echo twig_escape_filter($this->env, ($context["db_collation"] ?? null), "html", null, true);
                echo "\">
      ";
            }
            // line 52
            echo "
      <div class=\"card mb-2\">
        <div class=\"card-header\">";
            // line 54
            echo PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Rename database to"), true);
            echo "</div>
        <div class=\"card-body\">
          <div class=\"mb-3 row g-3\">
            <div class=\"col-auto\">
              <label class=\"visually-hidden\" for=\"new_db_name\">";
echo _gettext("New database name");
            // line 58
            echo "</label>
              <input class=\"form-control textfield\" id=\"new_db_name\" type=\"text\" name=\"newname\" maxlength=\"64\" required>
            </div>
          </div>

          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"checkbox\" name=\"adjust_privileges\" value=\"1\" id=\"checkbox_adjust_privileges\"";
            // line 65
            if (($context["has_adjust_privileges"] ?? null)) {
                echo " checked";
            } else {
                echo " title=\"";
echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details.");
                // line 66
                echo "\" disabled";
            }
            echo ">
            <label class=\"form-check-label\" for=\"checkbox_adjust_privileges\">
              ";
echo _gettext("Adjust privileges");
            // line 69
            echo "              ";
            echo PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
            echo "
            </label>
          </div>
        </div>

        <div class=\"card-footer text-end\">
          <input class=\"btn btn-primary\" type=\"submit\" value=\"";
echo _gettext("Go");
            // line 75
            echo "\">
        </div>
      </div>
    </form>
  ";
        }
        // line 80
        echo "
  ";
        // line 81
        if (($context["is_drop_database_allowed"] ?? null)) {
            // line 82
            echo "    <div class=\"card mb-2\">
      <div class=\"card-header\">";
            // line 83
            echo PhpMyAdmin\Html\Generator::getIcon("b_deltbl", _gettext("Remove database"), true);
            echo "</div>
      <div class=\"card-body\">
        <div class=\"card-text\">
          ";
            // line 86
            echo PhpMyAdmin\Html\Generator::linkOrButton(PhpMyAdmin\Url::getFromRoute("/sql"), ["sql_query" => ("DROP DATABASE " . PhpMyAdmin\Util::backquote(            // line 89
($context["db"] ?? null))), "back" => PhpMyAdmin\Url::getFromRoute("/database/operations"), "goto" => PhpMyAdmin\Url::getFromRoute("/"), "reload" => true, "purge" => true, "message_to_show" => twig_escape_filter($this->env, twig_sprintf(_gettext("Database %s has been dropped."), PhpMyAdmin\Util::backquote(            // line 94
($context["db"] ?? null)))), "db" => null], _gettext("Drop the database (DROP)"), ["id" => "drop_db_anchor", "class" => "ajax text-danger"]);
            // line 102
            echo "
          ";
            // line 103
            echo PhpMyAdmin\Html\MySQLDocumentation::show("DROP_DATABASE");
            echo "
        </div>
      </div>
    </div>
  ";
        }
        // line 108
        echo "
  <form id=\"copy_db_form\" class=\"ajax\" method=\"post\" action=\"";
        // line 109
        echo PhpMyAdmin\Url::getFromRoute("/database/operations");
        echo "\">
    ";
        // line 110
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        echo "
    <input type=\"hidden\" name=\"db_copy\" value=\"true\">

    ";
        // line 113
        if ( !twig_test_empty(($context["db_collation"] ?? null))) {
            // line 114
            echo "      <input type=\"hidden\" name=\"db_collation\" value=\"";
            echo twig_escape_filter($this->env, ($context["db_collation"] ?? null), "html", null, true);
            echo "\">
    ";
        }
        // line 116
        echo "
    <div class=\"card mb-2\">
      <div class=\"card-header\">";
        // line 118
        echo PhpMyAdmin\Html\Generator::getIcon("b_edit", _gettext("Copy database to"), true);
        echo "</div>
      <div class=\"card-body\">
        <div class=\"mb-3 row g-3\">
          <div class=\"col-auto\">
            <label class=\"visually-hidden\" for=\"renameDbNameInput\">";
echo _gettext("Database name");
        // line 122
        echo "</label>
            <input class=\"form-control textfield\" id=\"renameDbNameInput\" type=\"text\" maxlength=\"64\" name=\"newname\" required>
          </div>
        </div>

        <div class=\"mb-3\">
          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio1\" value=\"structure\">
            <label class=\"form-check-label\" for=\"whatRadio1\">
              ";
echo _gettext("Structure only");
        // line 132
        echo "            </label>
          </div>
          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio2\" value=\"data\" checked>
            <label class=\"form-check-label\" for=\"whatRadio2\">
              ";
echo _gettext("Structure and data");
        // line 138
        echo "            </label>
          </div>
          <div class=\"form-check\">
            <input class=\"form-check-input\" type=\"radio\" name=\"what\" id=\"whatRadio3\" value=\"dataonly\">
            <label class=\"form-check-label\" for=\"whatRadio3\">
              ";
echo _gettext("Data only");
        // line 144
        echo "            </label>
          </div>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"create_database_before_copying\" value=\"1\" id=\"checkbox_create_database_before_copying\" checked>
          <label class=\"form-check-label\" for=\"checkbox_create_database_before_copying\">";
echo _gettext("CREATE DATABASE before copying");
        // line 150
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"drop_if_exists\" value=\"true\" id=\"checkbox_drop\">
          <label class=\"form-check-label\" for=\"checkbox_drop\">";
        // line 155
        echo twig_escape_filter($this->env, twig_sprintf(_gettext("Add %s"), "DROP TABLE / DROP VIEW"), "html", null, true);
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"sql_auto_increment\" value=\"1\" id=\"checkbox_auto_increment\" checked>
          <label class=\"form-check-label\" for=\"checkbox_auto_increment\">";
echo _gettext("Add AUTO_INCREMENT value");
        // line 160
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"add_constraints\" value=\"1\" id=\"checkbox_constraints\" checked>
          <label class=\"form-check-label\" for=\"checkbox_constraints\">";
echo _gettext("Add constraints");
        // line 165
        echo "</label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"adjust_privileges\" value=\"1\" id=\"checkbox_privileges\"";
        // line 170
        if (($context["has_adjust_privileges"] ?? null)) {
            echo " checked";
        } else {
            echo " title=\"";
echo _gettext("You don't have sufficient privileges to perform this operation; Please refer to the documentation for more details.");
            // line 171
            echo "\" disabled";
        }
        echo ">
          <label class=\"form-check-label\" for=\"checkbox_privileges\">
            ";
echo _gettext("Adjust privileges");
        // line 174
        echo "            ";
        echo PhpMyAdmin\Html\MySQLDocumentation::showDocumentation("faq", "faq6-39");
        echo "
          </label>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"switch_to_new\" value=\"true\" id=\"checkbox_switch\"";
        // line 179
        echo ((($context["switch_to_new"] ?? null)) ? (" checked") : (""));
        echo ">
          <label class=\"form-check-label\" for=\"checkbox_switch\">";
echo _gettext("Switch to copied database");
        // line 180
        echo "</label>
        </div>
      </div>

      <div class=\"card-footer text-end\">
        <input class=\"btn btn-primary\" type=\"submit\" name=\"submit_copy\" value=\"";
echo _gettext("Go");
        // line 185
        echo "\">
      </div>
    </div>
  </form>

  <form id=\"change_db_charset_form\" class=\"ajax\" method=\"post\" action=\"";
        // line 190
        echo PhpMyAdmin\Url::getFromRoute("/database/operations/collation");
        echo "\">
    ";
        // line 191
        echo PhpMyAdmin\Url::getHiddenInputs(($context["db"] ?? null));
        echo "

    <div class=\"card mb-2\">
      <div class=\"card-header\">";
        // line 194
        echo PhpMyAdmin\Html\Generator::getIcon("s_asci", _gettext("Collation"), true);
        echo "</div>
      <div class=\"card-body\">
        <div class=\"mb-3 row g-3\">
          <div class=\"col-auto\">
            <label class=\"visually-hidden\" for=\"select_db_collation\">";
echo _gettext("Collation");
        // line 198
        echo "</label>
            <select class=\"form-control\" lang=\"en\" dir=\"ltr\" name=\"db_collation\" id=\"select_db_collation\">
              <option value=\"\"></option>
              ";
        // line 201
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["charsets"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["charset"]) {
            // line 202
            echo "                <optgroup label=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "getName", [], "method", false, false, false, 202), "html", null, true);
            echo "\" title=\"";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["charset"], "getDescription", [], "method", false, false, false, 202), "html", null, true);
            echo "\">
                  ";
            // line 203
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable((($__internal_compile_0 = ($context["collations"] ?? null)) && is_array($__internal_compile_0) || $__internal_compile_0 instanceof ArrayAccess ? ($__internal_compile_0[twig_get_attribute($this->env, $this->source, $context["charset"], "getName", [], "method", false, false, false, 203)] ?? null) : null));
            foreach ($context['_seq'] as $context["_key"] => $context["collation"]) {
                // line 204
                echo "                    <option value=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 204), "html", null, true);
                echo "\" title=\"";
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getDescription", [], "method", false, false, false, 204), "html", null, true);
                echo "\"";
                echo (((($context["db_collation"] ?? null) == twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 204))) ? (" selected") : (""));
                echo ">
                      ";
                // line 205
                echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->source, $context["collation"], "getName", [], "method", false, false, false, 205), "html", null, true);
                echo "
                    </option>
                  ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['collation'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 208
            echo "                </optgroup>
              ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['charset'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 210
        echo "            </select>
          </div>
        </div>

        <div class=\"form-check\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"change_all_tables_collations\" id=\"checkbox_change_all_tables_collations\">
          <label class=\"form-check-label\" for=\"checkbox_change_all_tables_collations\">";
echo _gettext("Change all tables collations");
        // line 216
        echo "</label>
        </div>
        <div class=\"form-check\" id=\"span_change_all_tables_columns_collations\">
          <input class=\"form-check-input\" type=\"checkbox\" name=\"change_all_tables_columns_collations\" id=\"checkbox_change_all_tables_columns_collations\">
          <label class=\"form-check-label\" for=\"checkbox_change_all_tables_columns_collations\">";
echo _gettext("Change all tables columns collations");
        // line 220
        echo "</label>
        </div>
      </div>

      <div class=\"card-footer text-end\">
        <input class=\"btn btn-primary\" type=\"submit\" value=\"";
echo _gettext("Go");
        // line 225
        echo "\">
      </div>
    </div>
  </form>

</div>
";
    }

    public function getTemplateName()
    {
        return "database/operations/index.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  455 => 225,  447 => 220,  440 => 216,  431 => 210,  424 => 208,  415 => 205,  406 => 204,  402 => 203,  395 => 202,  391 => 201,  386 => 198,  378 => 194,  372 => 191,  368 => 190,  361 => 185,  353 => 180,  348 => 179,  339 => 174,  332 => 171,  326 => 170,  320 => 165,  312 => 160,  303 => 155,  296 => 150,  287 => 144,  279 => 138,  271 => 132,  259 => 122,  251 => 118,  247 => 116,  241 => 114,  239 => 113,  233 => 110,  229 => 109,  226 => 108,  218 => 103,  215 => 102,  213 => 94,  212 => 89,  211 => 86,  205 => 83,  202 => 82,  200 => 81,  197 => 80,  190 => 75,  179 => 69,  172 => 66,  166 => 65,  158 => 58,  150 => 54,  146 => 52,  140 => 50,  138 => 49,  131 => 45,  126 => 44,  124 => 43,  118 => 39,  110 => 34,  103 => 30,  96 => 27,  92 => 26,  88 => 25,  85 => 24,  78 => 19,  69 => 14,  66 => 13,  58 => 9,  53 => 7,  48 => 6,  46 => 5,  41 => 3,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "database/operations/index.twig", "/var/www/api/public/pma/templates/database/operations/index.twig");
    }
}
