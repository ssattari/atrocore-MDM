<?php
/**
 * AtroCore Software
 *
 * This source file is available under GNU General Public License version 3 (GPLv3).
 * Full copyright and license information is available in LICENSE.txt, located in the root directory.
 *
 * @copyright  Copyright (c) AtroCore GmbH (https://www.atrocore.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/)
 */

namespace Atro\Migrations;

use Atro\Core\Migration\Base;
use Atro\ORM\DB\RDB\Mapper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Util;

class V1Dot10Dot50 extends Base
{
    public function getMigrationDateTime(): ?\DateTime
    {
        return new \DateTime('2024-07-31 11:00:00');
    }

    public function up(): void
    {
        if ($this->isPgSQL()) {
            $this->exec("CREATE TABLE notification_template (id VARCHAR(24) NOT NULL, name VARCHAR(255) DEFAULT NULL, deleted BOOLEAN DEFAULT 'false', code VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, data TEXT DEFAULT NULL, PRIMARY KEY(id));");
            $this->exec("CREATE UNIQUE INDEX UNIQ_C270272677153098EB3B4E33 ON notification_template (code, deleted);");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_TEMPLATE_NAME ON notification_template (name, deleted);");
            $this->exec("COMMENT ON COLUMN notification_template.data IS '(DC2Type:jsonObject)'");
            $this->exec("CREATE TABLE notification_profile (id VARCHAR(24) NOT NULL, name VARCHAR(255) DEFAULT NULL, deleted BOOLEAN DEFAULT 'false', description TEXT DEFAULT NULL, is_active BOOLEAN DEFAULT 'false' NOT NULL, PRIMARY KEY(id))");

            $this->exec("CREATE TABLE notification_rule (id VARCHAR(24) NOT NULL, name VARCHAR(255) DEFAULT NULL, deleted BOOLEAN DEFAULT 'false', description TEXT DEFAULT NULL, entity VARCHAR(255) DEFAULT NULL, occurrence VARCHAR(255) DEFAULT NULL, is_active BOOLEAN DEFAULT 'false' NOT NULL, ignore_self_action BOOLEAN DEFAULT 'false' NOT NULL, as_owner BOOLEAN DEFAULT 'false' NOT NULL, as_follower BOOLEAN DEFAULT 'false' NOT NULL, as_assignee BOOLEAN DEFAULT 'false' NOT NULL, as_team_member BOOLEAN DEFAULT 'false' NOT NULL, as_notification_profile BOOLEAN DEFAULT 'false' NOT NULL, data TEXT DEFAULT NULL, notification_profile_id VARCHAR(24) DEFAULT NULL, PRIMARY KEY(id));");
            $this->exec("CREATE UNIQUE INDEX IDX_NOTIFICATION_RULE_UNIQUE_NOTIFICATION_RULES ON notification_rule (notification_profile_id, entity, occurrence, deleted);");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_RULE_NOTIFICATION_PROFILE_ID ON notification_rule (notification_profile_id, deleted);");
            $this->exec("COMMENT ON COLUMN notification_rule.data IS '(DC2Type:jsonObject)';");
            $this->exec("ALTER TABLE notification_profile ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_profile ADD modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_profile ADD created_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_profile ADD modified_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_PROFILE_CREATED_BY_ID ON notification_profile (created_by_id, deleted);");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_PROFILE_MODIFIED_BY_ID ON notification_profile (modified_by_id, deleted);");
            $this->exec("ALTER TABLE notification_rule ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_rule ADD modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_rule ADD created_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_rule ADD modified_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_RULE_CREATED_BY_ID ON notification_rule (created_by_id, deleted);");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_RULE_MODIFIED_BY_ID ON notification_rule (modified_by_id, deleted);");
            $this->exec("ALTER TABLE notification_template ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_template ADD modified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_template ADD created_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("ALTER TABLE notification_template ADD modified_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_TEMPLATE_CREATED_BY_ID ON notification_template (created_by_id, deleted)");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_TEMPLATE_MODIFIED_BY_ID ON notification_template (modified_by_id, deleted)");
        } else {
            $this->exec("CREATE TABLE notification_template (id VARCHAR(24) NOT NULL, name VARCHAR(255) DEFAULT NULL, deleted TINYINT(1) DEFAULT '0', code VARCHAR(255) DEFAULT NULL, type VARCHAR(255) DEFAULT NULL, data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:jsonObject)', UNIQUE INDEX UNIQ_C270272677153098EB3B4E33 (code, deleted), INDEX IDX_NOTIFICATION_TEMPLATE_NAME (name, deleted), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;");
            $this->exec("CREATE TABLE notification_profile (id VARCHAR(24) NOT NULL, name VARCHAR(255) DEFAULT NULL, deleted TINYINT(1) DEFAULT '0', description LONGTEXT DEFAULT NULL, is_active TINYINT(1) DEFAULT '0' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;");
            $this->exec("CREATE TABLE notification_rule (id VARCHAR(24) NOT NULL, name VARCHAR(255) DEFAULT NULL, deleted TINYINT(1) DEFAULT '0', description VARCHAR(255) DEFAULT NULL, entity VARCHAR(255) DEFAULT NULL, occurrence VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) DEFAULT '0' NOT NULL, ignore_self_action TINYINT(1) DEFAULT '0' NOT NULL, as_owner TINYINT(1) DEFAULT '0' NOT NULL, as_follower TINYINT(1) DEFAULT '0' NOT NULL, as_assignee TINYINT(1) DEFAULT '0' NOT NULL, as_team_member TINYINT(1) DEFAULT '0' NOT NULL, as_notification_profile TINYINT(1) DEFAULT '0' NOT NULL, data LONGTEXT DEFAULT NULL COMMENT '(DC2Type:jsonObject)', notification_profile_id VARCHAR(24) DEFAULT NULL, UNIQUE INDEX IDX_NOTIFICATION_RULE_UNIQUE_NOTIFICATION_RULES (notification_profile_id, entity, occurrence, deleted), INDEX IDX_NOTIFICATION_RULE_NOTIFICATION_PROFILE_ID (notification_profile_id, deleted), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;");
            $this->exec("ALTER TABLE notification_profile ADD created_at DATETIME DEFAULT NULL, ADD modified_at DATETIME DEFAULT NULL, ADD created_by_id VARCHAR(24) DEFAULT NULL, ADD modified_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_PROFILE_CREATED_BY_ID ON notification_profile (created_by_id, deleted);");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_PROFILE_MODIFIED_BY_ID ON notification_profile (modified_by_id, deleted);");
            $this->exec("ALTER TABLE notification_rule ADD created_at DATETIME DEFAULT NULL, ADD modified_at DATETIME DEFAULT NULL, ADD created_by_id VARCHAR(24) DEFAULT NULL, ADD modified_by_id VARCHAR(24) DEFAULT NULL, CHANGE description description LONGTEXT DEFAULT NULL;");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_RULE_CREATED_BY_ID ON notification_rule (created_by_id, deleted);");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_RULE_MODIFIED_BY_ID ON notification_rule (modified_by_id, deleted);");
            $this->exec("ALTER TABLE notification_template ADD created_at DATETIME DEFAULT NULL, ADD modified_at DATETIME DEFAULT NULL, ADD created_by_id VARCHAR(24) DEFAULT NULL, ADD modified_by_id VARCHAR(24) DEFAULT NULL;");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_TEMPLATE_CREATED_BY_ID ON notification_template (created_by_id, deleted);");
            $this->exec("CREATE INDEX IDX_NOTIFICATION_TEMPLATE_MODIFIED_BY_ID ON notification_template (modified_by_id, deleted)");
        }

        $this->getConfig()->set('sendOutNotifications', !$this->getConfig()->get('disableEmailDelivery'));

        self::createNotificationDefaultNotificationProfile($this->getConnection(), $this->getConfig());

        try {
            $preferences = $this->getConnection()->createQueryBuilder()
                ->select('id', 'data')
                ->from('preferences')
                ->fetchAllAssociative();

            foreach ($preferences as $preference) {
                $data = @json_decode($preference['data'], true);
                if (empty($data)) {
                    continue;
                }
                $data['receiveNotifications'] = true;
                $data['notificationProfileId'] = "default";

                $this->getConnection()->createQueryBuilder()
                    ->update('preferences')
                    ->set('data', ':data')
                    ->where('id = :id')
                    ->setParameter('id', $preference['id'])
                    ->setParameter('data', json_encode($data))
                    ->executeStatement();
            }
        } catch (\Throwable $e) {

        }
    }

    public function down(): void
    {
        $this->exec("DROP TABLE notification_rule;");
        $this->exec("DROP TABLE notification_profile;");
        $this->exec("DROP TABLE notification_template;");
        $this->getConfig()->set('disableEmailDelivery', !$this->getConfig()->get('sendOutNotifications'));

    }

    protected function exec(string $query): void
    {
        try {
            $this->getPDO()->exec($query);
        } catch (\Throwable $e) {
        }
    }

    public static function getDefaultRules()
    {
        $defaultProfileId = 'defaultProfileId';
        return [
            [
                "id"                      => Util::generateId(),
                "name"                    => "Entity Update",
                "entity"                  => '',
                "occurrence"              => 'updating',
                "notification_profile_id" => $defaultProfileId,
                "is_active"               => true,
                "ignore_self_action"      => true,
                "as_owner"                => true,
                "as_follower"             => true,
                "as_assignee"             => true,
                "as_team_member"          => false,
                "as_notification_profile" => false,
                "data"                    => [
                    "field" => [
                        "systemActive"     => true,
                        "emailActive"      => true,
                        "systemTemplateId" => "systemUpdateEntity",
                        "emailTemplateId"  => "emailUpdateEntity"
                    ],
                ],
                "templates"               => [
                    "system" => [
                        "id"   => "systemUpdateEntity",
                        "name" => "Entity Updated",
                        "data" => [
                            "field" => [
                                "body"     => '<p>{{actionUser.name}} made update  on {{entityName}} <a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a>.</p>',
                                "bodyDeDe" => '<p>{{actionUser.name}} hat eine Aktualisierung an {{entityName}} vorgenommen <a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a>.</p>',
                                "bodyUkUa" => '<p>{{actionUser.name}} зробив оновлення для {{entityName}} <a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a>.</p>'
                            ]
                        ]
                    ],
                    "email"  => [
                        "id"   => "emailUpdateEntity",
                        "name" => "Entity Updated",
                        "data" => [
                            "field" => [
                                "subject"     => "{% set  hasAssignment = 'assignedUser' in updateData['fields']  %}

{% set isOnly = updateData['fields']|length == 1 %}

{% set assignedUserId =  entity.assignedUserId %}


{% if hasAssignment  and isOnly and assignedUserId and notifyUser.id == assignedUserId  %}
     {% set assignedUserName =  updateData['attributes']['became']['assignedUserName'] %}
    Assigned to you: [{{entityType}}] {{entity.name | raw}}
{% elseif  hasAssignment  and notifyUser.id == assignedUserId %}
    Assigned to you and update [{{entityType}}] {{entity.name | raw}}
{% else %}
    Update: [{{ entityName }}] {{entity.name | raw}}
{% endif %}",
                                "subjectDeDe" => "{% set  hasAssignment = 'assignedUser' in updateData['fields']  %}

{% set isOnly = updateData['fields']|length == 1 %}

{% set assignedUserId =  entity.assignedUserId %}


{% if hasAssignment  and isOnly and assignedUserId and notifyUser.id == assignedUserId  %}
     {% set assignedUserName =  updateData['attributes']['became']['assignedUserName'] %}
    Ihnen zugewiesen: [{{entityType}}] {{entity.name | raw}}
{% elseif  hasAssignment  and notifyUser.id == assignedUserId %}
    Ihnen zugewiesen und aktualisieren [{{entityType}}] {{entity.name | raw}}
{% else %}
    Update: [{{ entityName }}] {{entity.name | raw}}
{% endif %}",
                                "subjectUkUa" => "{% set  hasAssignment = 'assignedUser' in updateData['fields']  %}

{% set isOnly = updateData['fields']|length == 1 %}

{% set assignedUserId =  entity.assignedUserId %}


{% if hasAssignment  and isOnly and assignedUserId and notifyUser.id == assignedUserId  %}
     {% set assignedUserName =  updateData['attributes']['became']['assignedUserName'] %}
    Ihnen zugewiesen: [{{entityType}}] {{entity.name | raw}}
{% elseif  hasAssignment  and notifyUser.id == assignedUserId %}
    Ihnen zugewiesen und aktualisieren [{{entityType}}] {{entity.name | raw}}
{% else %}
    Update: [{{ entityName }}] {{entity.name | raw}}
{% endif %}",
                                "body"        => "{% set  hasAssignment = 'assignedUser' in updateData['fields'] %}

{% set isOnly = updateData['fields']|length == 1 %}

{% set assignedUserId =  entity.assignedUserId %}

{% set language = language ?? 'en_US' %}

{% macro changedFieldTable(updateData, language, entityType) %}
    <style>
        ins {
            color: green;
            background: #dfd;
            text-decoration: none;
        }
        del {
            color: red;
            background: #fdd;
            text-decoration: none;
        }
    </style>

    {%  set textFields  = [] %}

    <table>
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        {% for field in updateData['fields'] %}
            {% if updateData['fieldTypes'][field] in ['text','wysiwyg', 'markdown'] %}
                {%  set textFields  = textFields|merge([field]) %}
             {% else %}
             <tr style=\"padding: 10px 0;\">
            {% if updateData['fieldTypes'][field] in ['extensibleEnum', 'link', 'measure', 'extensibleMultiEnum', 'file'] %}
                {% set fieldKeyName = field ~ 'Name' %}
                {% set was = updateData['attributes']['was'][fieldKeyName] %}
                {% set became = updateData['attributes']['became'][fieldKeyName] %}
            {% else %}
                {% set was = updateData['attributes']['was'][field] %}
                {% set became = updateData['attributes']['became'][field] %}
            {% endif %}

            {% if  updateData['fieldTypes'][field]  == 'extensibleMultiEnum' %}
                {% set was = updateData['attributes']['was'][field]|map(id => was[id])|join(', ') %}
                {% set became = updateData['attributes']['became'][field]|map(id => became[id])|join(', ') %}
            {% endif %}

            {% if updateData['fieldTypes'][field] == 'enum' %}
                {% set was = translateOption(was, language, field, entityType) %}
                {% set became = translateOption(became, language, field, entityType) %}
            {% endif %}

            {% if updateData['fieldTypes'][field] == 'multiEnum' %}
                {% set was = was|map(v => translateOption(v, language, field, entityType))|join(', ') %}
                {% set became = became|map(v => translateOption(v, language,  field, entityType))|join(', ') %}
            {% endif %}
              {% if  updateData['fieldTypes'][field]  == 'bool' %}
                  {%  if was is not null %}
                      {% set was = was ?  translate('Yes',language): translate('No',language)  %}
                  {% endif %}
                 {%  if became is not null %}
                     {% set became = became ?  translate('Yes',language): translate('No',language)  %}
                 {% endif %}
             {% endif %}

            <td> <span style=\"padding: 15px 0\"> {{ translate(field, language, 'fields', entityType ) }}:</span></td>
            <td style=\"padding: 10px 0;\">{% if was %}<span style=\"padding:0 5px;margin-right:15px;background-color: #F5A8A880;text-decoration: line-through; \">{{ was }}</span> {% endif %}</td>
            <td style=\"padding: 10px 0;\">{% if became %}<span style=\"padding:0 5px; background-color: #A8F5B880;\">{{ became }}</span> {% endif %}</td>
        <tr>
            {% endif %}
        {% endfor %}
        </tbody>
    </table>

    {% for field in textFields %}
        <div style=\"margin: 15px 0\">    <span style=\"padding: 15px 0; margin-right:15px;\"> {{ translate(field, language, 'fields', entityType ) }}: </span> {{ updateData['diff'][field ]|raw }}</div>
        <br>
    {% endfor %}
{% endmacro %}

{% if hasAssignment  and isOnly and assignedUserId and notifyUser.id == assignedUserId%}
    {% set assignedUserName =  updateData['attributes']['became']['assignedUserName'] %}
   <p> {{ actionUser.name }} has assigned {{ entityName }} to  you.</p>
{% elseif  hasAssignment  and notifyUser.id == assignedUserId %}
   <p> {{ actionUser.name }} has assigned to you and made update on {{ entityName }}.</p>
{% else %}
    <p>{{ actionUser.name }} made update on {{ entityName }}.</p>
{% endif %}
{{ _self.changedFieldTable(updateData, language, entityType) }}
<p><a href=\"{{ entityUrl }}\">View</a></p>",
                                "bodyDeDe"    => "{% set  hasAssignment = 'assignedUser' in updateData['fields'] %}

{% set isOnly = updateData['fields']|length == 1 %}

{% set assignedUserId =  entity.assignedUserId %}

{% set language = language ?? 'en_US' %}

{% macro changedFieldTable(updateData, language, entityType) %}
    <style>
        ins {
            color: green;
            background: #dfd;
            text-decoration: none;
        }
        del {
            color: red;
            background: #fdd;
            text-decoration: none;
        }
    </style>

    {%  set textFields  = [] %}

    <table>
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        {% for field in updateData['fields'] %}
            {% if updateData['fieldTypes'][field] in ['text','wysiwyg', 'markdown'] %}
                {%  set textFields  = textFields|merge([field]) %}
             {% else %}
             <tr style=\"padding: 10px 0;\">
            {% if updateData['fieldTypes'][field] in ['extensibleEnum', 'link', 'measure', 'extensibleMultiEnum', 'file'] %}
                {% set fieldKeyName = field ~ 'Name' %}
                {% set was = updateData['attributes']['was'][fieldKeyName] %}
                {% set became = updateData['attributes']['became'][fieldKeyName] %}
            {% else %}
                {% set was = updateData['attributes']['was'][field] %}
                {% set became = updateData['attributes']['became'][field] %}
            {% endif %}

            {% if  updateData['fieldTypes'][field]  == 'extensibleMultiEnum' %}
                {% set was = updateData['attributes']['was'][field]|map(id => was[id])|join(', ') %}
                {% set became = updateData['attributes']['became'][field]|map(id => became[id])|join(', ') %}
            {% endif %}

            {% if updateData['fieldTypes'][field] == 'enum' %}
                {% set was = translateOption(was, language, field, entityType) %}
                {% set became = translateOption(became, language, field, entityType) %}
            {% endif %}

            {% if updateData['fieldTypes'][field] == 'multiEnum' %}
                {% set was = was|map(v => translateOption(v, language, field, entityType))|join(', ') %}
                {% set became = became|map(v => translateOption(v, language,  field, entityType))|join(', ') %}
            {% endif %}
              {% if  updateData['fieldTypes'][field]  == 'bool' %}
                  {%  if was is not null %}
                      {% set was = was ?  translate('Yes',language): translate('No',language)  %}
                  {% endif %}
                 {%  if became is not null %}
                     {% set became = became ?  translate('Yes',language): translate('No',language)  %}
                 {% endif %}
             {% endif %}
             
            <td> <span style=\"padding: 15px 0\"> {{ translate(field, language, 'fields', entityType ) }}:</span></td>
            <td style=\"padding: 10px 0;\">{% if was %}<span style=\"padding:0 5px;margin-right:15px;background-color: #F5A8A880;text-decoration: line-through; \">{{ was }}</span> {% endif %}</td>
            <td style=\"padding: 10px 0;\">{% if became %}<span style=\"padding:0 5px; background-color: #A8F5B880;\">{{ became }}</span> {% endif %}</td>
        <tr>
            {% endif %}
        {% endfor %}
        </tbody>
    </table>

    {% for field in textFields %}
        <div style=\"margin: 15px 0\">    <span style=\"padding: 15px 0; margin-right:15px;\"> {{ translate(field, language, 'fields', entityType ) }}: </span> {{ updateData['diff'][field ]|raw }}</div>
        <br>
    {% endfor %}
{% endmacro %}

{% if hasAssignment  and isOnly and assignedUserId and notifyUser.id == assignedUserId%}
    {% set assignedUserName =  updateData['attributes']['became']['assignedUserName'] %}
    <p>  {{ actionUser.name }} hat Ihnen {{ entityName }} zugewiesen. </p>
{% elseif  hasAssignment  and notifyUser.id == assignedUserId %}
   <p> {{ actionUser.name }} hat Ihnen zugewiesen und eine Aktualisierung an {{ entityName }} vorgenommen </p>
{% else %}
    <p>{{ actionUser.name }} hat eine Aktualisierung an {{ entityName }} vorgenommen.</p>
{% endif %}
{{ _self.changedFieldTable(updateData, language, entityType) }}
<p><a href=\"{{ entityUrl }}\">Siehe</a></p>",
                                "bodyUkUa"    => "{% set  hasAssignment = 'assignedUser' in updateData['fields'] %}

{% set isOnly = updateData['fields']|length == 1 %}

{% set assignedUserId =  entity.assignedUserId %}

{% set language = language ?? 'en_US' %}

{% macro changedFieldTable(updateData, language, entityType) %}
    <style>
        ins {
            color: green;
            background: #dfd;
            text-decoration: none;
        }
        del {
            color: red;
            background: #fdd;
            text-decoration: none;
        }
    </style>

    {%  set textFields  = [] %}

    <table>
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        {% for field in updateData['fields'] %}
            {% if updateData['fieldTypes'][field] in ['text','wysiwyg', 'markdown'] %}
                {%  set textFields  = textFields|merge([field]) %}
             {% else %}
             <tr style=\"padding: 10px 0;\">
            {% if updateData['fieldTypes'][field] in ['extensibleEnum', 'link', 'measure', 'extensibleMultiEnum', 'file'] %}
                {% set fieldKeyName = field ~ 'Name' %}
                {% set was = updateData['attributes']['was'][fieldKeyName] %}
                {% set became = updateData['attributes']['became'][fieldKeyName] %}
            {% else %}
                {% set was = updateData['attributes']['was'][field] %}
                {% set became = updateData['attributes']['became'][field] %}
            {% endif %}

            {% if  updateData['fieldTypes'][field]  == 'extensibleMultiEnum' %}
                {% set was = updateData['attributes']['was'][field]|map(id => was[id])|join(', ') %}
                {% set became = updateData['attributes']['became'][field]|map(id => became[id])|join(', ') %}
            {% endif %}

            {% if updateData['fieldTypes'][field] == 'enum' %}
                {% set was = translateOption(was, language, field, entityType) %}
                {% set became = translateOption(became, language, field, entityType) %}
            {% endif %}

            {% if updateData['fieldTypes'][field] == 'multiEnum' %}
                {% set was = was|map(v => translateOption(v, language, field, entityType))|join(', ') %}
                {% set became = became|map(v => translateOption(v, language,  field, entityType))|join(', ') %}
            {% endif %}
            
          {% if  updateData['fieldTypes'][field]  == 'bool' %}
              {%  if was is not null %}
                  {% set was = was ?  translate('Yes',language): translate('No',language)  %}
              {% endif %}
             {%  if became is not null %}
                 {% set became = became ?  translate('Yes',language): translate('No',language)  %}
             {% endif %}
         {% endif %}

            <td> <span style=\"padding: 15px 0\"> {{ translate(field, language, 'fields', entityType ) }}:</span></td>
            <td style=\"padding: 10px 0;\">{% if was %}<span style=\"padding:0 5px;margin-right:15px;background-color: #F5A8A880;text-decoration: line-through; \">{{ was }}</span> {% endif %}</td>
            <td style=\"padding: 10px 0;\">{% if became %}<span style=\"padding:0 5px; background-color: #A8F5B880;\">{{ became }}</span> {% endif %}</td>
        <tr>
            {% endif %}
        {% endfor %}
        </tbody>
    </table>

    {% for field in textFields %}
        <div style=\"margin: 15px 0\">    <span style=\"padding: 15px 0; margin-right:15px;\"> {{ translate(field, language, 'fields', entityType ) }}: </span> {{ updateData['diff'][field ]|raw }}</div>
        <br>
    {% endfor %}
{% endmacro %}

{% if hasAssignment  and isOnly and assignedUserId and notifyUser.id == assignedUserId%}
    {% set assignedUserName =  updateData['attributes']['became']['assignedUserName'] %}
   <p> {{ actionUser.name }} призначив вам {{ entityName }}</p>
{% elseif  hasAssignment  and notifyUser.id == assignedUserId %}
   <p>{{ actionUser.name }} призначив вам і оновив {{ entityName }}</p>
{% else %}
    <p>{{ actionUser.name }} виконано оновлення на {{ entityName }}.</p>
{% endif %}
{{ _self.changedFieldTable(updateData, language, entityType) }}
<p><a href=\"{{ entityUrl }}\">Вигляд</a></p>"
                            ]
                        ]
                    ]
                ]
            ],
            [
                "id"                      => Util::generateId(),
                "name"                    => "Note Creation Without parent",
                "entity"                  => 'Note',
                "occurrence"              => 'creation',
                "notification_profile_id" => $defaultProfileId,
                "is_active"               => true,
                "ignore_self_action"      => true,
                "as_owner"                => true,
                "as_follower"             => true,
                "as_assignee"             => true,
                "as_team_member"          => true,
                "as_notification_profile" => false,
                "data"                    => [
                    "field" => [
                        "systemActive"     => true,
                        "emailActive"      => true,
                        "systemTemplateId" => "systemNotePost",
                        "emailTemplateId"  => "emailNotePost"
                    ],
                ],
            ],
            [
                "id"                      => Util::generateId(),
                "name"                    => "Note Creation in Entity",
                "entity"                  => '',
                "occurrence"              => 'note_created',
                "notification_profile_id" => $defaultProfileId,
                "is_active"               => true,
                "ignore_self_action"      => true,
                "as_owner"                => true,
                "as_follower"             => true,
                "as_assignee"             => true,
                "as_team_member"          => false,
                "as_notification_profile" => false,
                "data"                    => [
                    "field" => [
                        "systemActive"     => true,
                        "emailActive"      => true,
                        "systemTemplateId" => "systemNotePost",
                        "emailTemplateId"  => "emailNotePost"
                    ],
                ],
                "templates"               => [
                    "system" => [
                        'id'   => 'systemNotePost',
                        'name' => 'Note Creation',
                        'data' => [
                            'field' => [
                                "body"     => '<p>{{actionUser.name}} posted  {% if parent %}  on {{parentName}} <a href="{{parentUrl}}">{{parent.name}}</a>. {% endif %}</p>
<p>{{entity.data.post}}</p>
{% if not parent %}
<p><a href="{{siteUrl}}/#Stream">View</a></p>
{% endif %}',
                                "bodyDeDe" => '<p>{{actionUser.name}} auf{% if parent %}  {{parentName}} <a href="{{parentUrl}}">{{parent.name}}</a> gepostet. {% endif %}</p>
<p>{{entity.data.post}}</p>
{% if not parent %}
<p><a href="{{siteUrl}}/#Stream">View</a></p>
{% endif %}',
                                "bodyUkUa" => '<p>{{actionUser.name}} опублікував {% if parent %} на {{parentName}} <a href="{{parentUrl}}">{{parent.name}}</a>. {% endif %}</p> <p>
<p>{{entity.data.post}}</p>
{% if not parent %}
<p><a href="{{siteUrl}}/#Stream">Вигляд</a></p>
{% endif %}'
                            ]
                        ]
                    ],
                    "email"  => [
                        'id'   => 'emailNotePost',
                        "name" => "Note creation",
                        "data" => [
                            "field" => [
                                "subject"     => 'Post: [{{ parentName }}] {{parent.name | raw}}',
                                "subjectDeDe" => 'Post: [{{ parentName }}] {{parent.name | raw}}',
                                "subjectUkUa" => 'Post: [{{ parentName }}] {{parent.name | raw}}',
                                "body"        => '<p>{{actionUser.name}} posted  {% if parent %}  on {{parentName}} {{parent.name}}. {% endif %}</p>
<p>{{entity.data.post | raw}}</p>
{% if parent %}
<p><a href="{{parentUrl}}">View</a></p>
{% else %}
<p><a href="{{siteUrl}}/#Stream">View</a></p>
{% endif %}',
                                "bodyDeDe"    => '<p>{{actionUser.name}} auf{% if parent %}  {{parentName}} {{parent.name}} gepostet. {% endif %}</p>
<p>{{entity.data.post  | raw}}</p>
{% if parent %}
<p><a href="{{parentUrl}}">View</a></p>
{% else %}
<p><a href="{{siteUrl}}/#Stream">View</a></p>
{% endif %}',
                                "bodyUkUa"    => '<p>{{actionUser.name}} опублікував {% if parent %} на {{parentName}} {{parent.name}}. {% endif %}</p> <p>
<p>{{entity.data.post | raw}}</p>
{% if parent %}
<p><a href="{{parentUrl}}">Вигляд</a></p>
{% else %}
<p><a href="{{siteUrl}}/#Stream">Вигляд</a></p>
{% endif %}'
                            ]
                        ],
                    ]
                ]
            ],
            [
                "id"                      => Util::generateId(),
                "name"                    => "Mention",
                "entity"                  => '',
                "occurrence"              => 'mentioned',
                "notification_profile_id" => $defaultProfileId,
                "is_active"               => true,
                "ignore_self_action"      => true,
                "as_owner"                => false,
                "as_follower"             => false,
                "as_assignee"             => false,
                "as_team_member"          => false,
                "as_notification_profile" => false,
                "data"                    => [
                    "field" => [
                        "systemActive"     => true,
                        "emailActive"      => true,
                        "systemTemplateId" => "systemMention",
                        "emailTemplateId"  => "emailMention"
                    ]
                ],
                "templates"               => [
                    "system" => [
                        "id"   => "systemMention",
                        "name" => 'Mention',
                        "data" => [
                            "field" => [
                                "body"     => '<p>You were mentioned in post by {{actionUser.name}}.</p>
{% if parent %}
<p>Related to: {{parentName}} <a href="{{parentUrl}}">{{parent.name}}</a></p>
{% endif  %}
<p>{{entity.data.post}}</p>
{% if not parent %}
<p><a href="{{siteUrl}}/#Stream">View</a></p>
{% endif %}',
                                "bodyDeDe" => '<p>Sie wurden in einem Beitrag von {{actionUser.name}} erwähnt.</p>
{% if parent %}
<p>Verwandt mit:  {{parentName}} <a href="{{parentUrl}}">{{parent.name}}</a></p>
{% endif  %}
<p>{{entity.data.post}}</p>
{% if not parent %}
<p><a href="{{siteUrl}}/#Stream">Siehe</a></p>
{% endif %}',
                                "bodyUkUa" => '<p>Вас було згадано у дописі користувача {{actionUser.name}}.</p>{% if parent %}
<p>Пов\'язано з:  {{parentName}} <a href="{{parentUrl}}">{{parent.name}}</a></p>
{% endif  %}
<p>{{entity.data.post}}</p>
{% if not parent %}
<p><a href="{{siteUrl}}/#Stream">Вигляд</a></p>
{% endif %}'
                            ]
                        ]
                    ],
                    "email"  => [
                        "id"   => "emailMention",
                        "name" => "Mention",
                        "data" => [
                            "field" => [
                                "subject"     => "You were mentioned",
                                "subjectDeDe" => "Sie wurden erwähnt",
                                "subjectUkUa" => "Тебе згадували",
                                "body"        => '<p>You were mentioned in post by {{actionUser.name}}.</p>
{% if parent %}
<p>Related to: {{parentName}}</p>
{% endif  %}
<p>{{entity.data.post | raw}}</p>
{% if parent %}
<p><a href="{{parentUrl}}">View</a></p>
{% else %}
<p><a href="{{siteUrl}}/#Stream">View</a></p>
{% endif %}',
                                "bodyDeDe"    => '
 <p>Sie wurden in einem Beitrag von {{actionUser.name}} erwähnt.</p>
{% if parent %}
<p>Verwandt mit:  {{parentName}}</p>
{% endif  %}
<p>{{entity.data.post | raw}}</p>
{% if parent %}
<p><a href="{{parentUrl}}">Siehe</a></p>
{% else %}
<p><a href="{{siteUrl}}/#Stream">Siehe</a></p>
{% endif %}',
                                "bodyUkUa"    => '<p>Вас було згадано у дописі користувача {{actionUser.name}}.</p>{% if parent %}
<p>Пов\'язано з:  {{parentName}}</p>
{% endif  %}
<p>{{entity.data.post | raw}}</p>
{% if parent %}
<p><a href="{{parentUrl}}">Вигляд</a></p>
{% else %}
<p><a href="{{siteUrl}}/#Stream">Вигляд</a></p>
{% endif %}'
                            ]
                        ]
                    ]
                ]
            ],
            [
                "id"                      => Util::generateId(),
                "name"                    => "Assignment/Ownership",
                "entity"                  => '',
                "occurrence"              => 'ownership_assignment',
                "notification_profile_id" => $defaultProfileId,
                "is_active"               => true,
                "ignore_self_action"      => true,
                "as_owner"                => true,
                "as_follower"             => false,
                "as_assignee"             => true,
                "as_team_member"          => false,
                "as_notification_profile" => false,
                "data"                    => [
                    "field" => [
                        "systemActive"     => true,
                        "emailActive"      => true,
                        "systemTemplateId" => "systemOwnerAssign",
                        "emailTemplateId"  => "emailOwnerAssign"
                    ],
                ],
                "templates"               => [
                    "system" => [
                        "id"   => 'systemOwnerAssign',
                        "type" => "system",
                        "name" => "Assignment/Ownership",
                        "data" => [
                            "field" => [
                                "body"     => '{% if isAssignment %}
<p>{{actionUser.name}} has assigned {{entityName}} to  {% if notifyUser.id == assignedUser.id %} you. {% else %}  {{assignedUser.name}}. {% endif %} </p>
<p><a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a></p>
{% else %}
<p>{{actionUser.name}} has set {% if notifyUser.id == ownerUser.id %} you {% else %}  {{ownerUser.name}} {% endif %} as owner for {{entityName}}.</p>
<p><a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a></p>
{% endif %}
',
                                "bodyDeDe" => '
{% if  isAssignment %}
<p>{{actionUser.name}} hat Ihnen {{entityName}}   {% if notifyUser.id == assignedUser.id %} zugewiesen. {% else %}  {{assignedUser.name}}. {% endif %} </p>
<p><a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a></p>
{% else %}
<p>{{actionUser.name}} hat {% if notifyUser.id == ownerUser.id %} Sie {% else %}  {{ownerUser.name}} {% endif %} als Eigentümer für {{entityName}}.
<p><a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a></p>
{% endif %} 
',
                                "bodyUkUa" => '
{% if isAssignment %}
<p>{{actionUser.name}} призначив {% if notifyUser.id == assignedUser.id %} вам {% else %} {{assignedUser.name}}. {% endif %} {{entityName}}.</p>
<p><a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a></p>
{% else %}
<p>{{actionUser.name}} встановив {% if notifyUser.id == ownerUser.id %} вам {% else %}  {{ownerUser.name}} {% endif  %} як власника для {{entityName}}.</p>
<p><a href="{{entityUrl}}"><strong>{{entity.name}}</strong></a></p>
{% endif %}
                              '
                            ]
                        ]
                    ],
                    "email"  => [
                        "id"   => 'emailOwnerAssign',
                        "type" => "system",
                        "name" => "Assignment/Ownership",
                        "data" => [
                            "field" => [
                                "subject"     => '{% if isAssignment %}
Assigned to {% if notifyUser.id == assignedUser.id %} you {% else %}  {{assignedUser.name | raw}} {% endif %}: [{{entityType}}] {{entity.name | raw}}
{% else %}
Marked as owner: [{{entityType}}] {{entity.name | raw}}
{% endif %}',
                                "subjectDeDe" => '{% if isAssignment  %}
Ihnen {% if notifyUser.id == assignedUser.id %} zugewiesen. {% else %}  {{assignedUser.name | raw}} {% endif %} : [{{entityType}}] {{entity.name | raw}}
{% else %}
Markiert als Eigentümer: [{{entityType}}] {{entity.name | raw}}
{% endif %}',
                                "subjectUkUa" => '{% if isAssignment %}
Призначено {% if notifyUser.id == assignedUser.id %} вам {% else %} {{assignedUser.name | raw}} {% endif %}: [{{entityType}}] {{entity.name | raw}}
{% else %}
Позначено як власник: [{{entityType}}] {{entity.name | raw}}
{% endif %}',
                                "body"        => '{% if isAssignment %}
<p>{{actionUser.name}} has assigned {{entityName}} to  {% if notifyUser.id == assignedUser.id %} you. {% else %}  {{assignedUser.name}}. {% endif %} </p>
<p><strong>{{entity.name}}</strong></p>
<p><a href="{{entityUrl}}">View</a></p>
{% else %}
<p>{{actionUser.name}} has set {% if notifyUser.id == ownerUser.id %} you {% else %}  {{ownerUser.name}} {% endif %} as owner for {{entityName}}.</p>
<p><strong>{{entity.name}}</strong></p>
<p><a href="{{entityUrl}}">View</a></p>
{% endif %}
',
                                "bodyDeDe"    => '
{% if isAssignment %}
<p>{{actionUser.name}} hat Ihnen {{entityName}}   {% if notifyUser.id == assignedUser.id %} zugewiesen. {% else %}  {{assignedUser.name}}. {% endif %} </p>
<p><strong>{{entity.name}}</strong></p>
<p><a href="{{entityUrl}}">Siehe</a></p>
{% else %}
<p>{{actionUser.name}} hat {% if notifyUser.id == ownerUser.id %} Sie {% else %}  {{ownerUser.name}} {% endif %} als Eigentümer für {{entityName}}.</p><p><strong>{{entity.name}}</strong></p>
<p><a href="{{entityUrl}}">Siehe</a></p>
{% endif %} 
',
                                "bodyUkUa"    => '
{% if isAssignment %}
<p>{{actionUser.name}} призначив {% if notifyUser.id == assignedUser.id %} вам {% else %} {{assignedUser.name}}. {% endif %} {{entityName}}.</p>
<p><strong>{{entity.name}}</strong></p>
<p><a href="{{entityUrl}}">Вигляд</a></p>
{% else %}
<p>{{actionUser.name}} встановив {% if notifyUser.id == ownerUser.id %} вам {% else %}  {{ownerUser.name}} {% endif  %} як власника для {{entityName}}.</p>
<p><strong>{{entity.name}}</strong></p>
<p><a href="{{entityUrl}}">Вигляд</a></p>
{% endif %}
                              '
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function createNotificationDefaultNotificationProfile(Connection $connection, Config $config)
    {
        $defaultProfileId = 'defaultProfileId';
        $defaultProfileName = 'Default Notification Profile';

        $config->set('defaultNotificationProfileId', $defaultProfileId);
        $config->set('defaultNotificationProfileName', $defaultProfileName);
        $config->save();

        try {
            $connection->createQueryBuilder()
                ->insert('notification_profile')
                ->values([
                    'id'        => ':id',
                    'name'      => ':name',
                    'is_active' => ':is_active',
                ])
                ->setParameter('id', 'defaultProfileId')
                ->setParameter('name', $defaultProfileName)
                ->setParameter('is_active', true, ParameterType::BOOLEAN)
                ->executeStatement();
        } catch (\Throwable $e) {

        }

        $rules = self::getDefaultRules();


        foreach ($rules as $rule) {
            if (!empty($rule['templates'])) {
                $templates = $rule['templates'];
                foreach ($templates as $type => $template) {
                    try {
                        $connection->createQueryBuilder()
                            ->insert('notification_template')
                            ->values([
                                'id'   => ':id',
                                'name' => ':name',
                                'data' => ':data',
                                'type' => ':type'
                            ])
                            ->setParameter('id', $template['id'])
                            ->setParameter('name', $template['name'])
                            ->setParameter('data', json_encode($template['data']))
                            ->setParameter('type', $type)
                            ->executeStatement();
                    } catch (\Throwable $e) {

                    }
                }

            }

            try {
                $query = $connection->createQueryBuilder()
                    ->insert('notification_rule');
                $values = [];
                foreach ($rule as $key => $value) {
                    if ($key === 'templates') continue;
                    $values[$key] = ":$key";
                    $query = $query
                        ->setParameter($key, is_array($value) ? json_encode($value) : $value, is_array($value) ? ParameterType::STRING : Mapper::getParameterType($value));
                }
                $query->values($values);
                $query->executeStatement();
            } catch (\Throwable $e) {
            }
        }
    }

}
