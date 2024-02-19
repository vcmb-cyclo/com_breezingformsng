#!/usr/bin/bash
# find -name '*.php' -exec perl -i -p -e 's/JFactory::getContainer\(\)\->query\(\)/JFactory::getContainer()->execute()/g;' {} \;
#find -name '*.php' -exec perl -i -p -e 's/JFactory::getDBO\(\)\->query\(\)/JFactory::getDBO()\->execute()/g;' {} \;
#find -name '*.php' -exec perl -i -p -e 's/\-\>query\(/->execute(/g;' {} \;
find -name '*.php' -exec perl -i -p -e "s/>isSite\(\)/>isClient('site')/g;" {} \;
find -name '*.php' -exec perl -i -p -e "s/>isAdmin\(\)/>isClient('administrator')/g;" {} \;
find -name '*.php' -exec perl -i -p -e "s/JRequest::get[^(]*\((\s*)'/JFactory::getApplication()->getInput()->get($1'/g;" {} \;
find -name '*.php' -exec perl -i -p -e "s/JRequest::checkToken\((\s*)'/JFactory::getApplication()->getInput()->get($1'/g;" {} \;
find -name '*.php' -exec perl -i -p -e "s/JRequest::setVar\((\s*)'/JFactory::getApplication()->getInput()->set($1'/g;" {} \;

# Comment
find -name '*.php' -exec perl -i -p -e "s|JHTML::_\('behavior.mootools'\);|/*** JHTML::_('behavior.mootools'); ***/|g;" {} \;
find -name '*.php' -exec perl -i -p -e "s|JHTML::_\('behavior.framework'\);|/*** JHTML::_('behavior.framework'); ***/|g;" {} \;
find -name '*.php' -exec perl -i -p -e "s|JHTML::_\('behavior.framework', true\);|/*** JHTML::_('behavior.framework', true); ***/|ig;" {} \;


find -name '*.php' -exec perl -i -p -e "s/'behavior.modal'/'bootstrap.modal'/g" {} \;
find -name '*.php' -exec perl -i -p -e "s/'behavior.tooltip'/'bootstrap.tooltip'/g" {} \;

find -name '*.php' -exec perl -i -p -e 's/JSubMenuHelper/JHtmlSidebar/g' {} \;
find -name '*.php' -exec perl -i -p -e 's/JArrayHelper::/Joomla\\Utilities\\ArrayHelper::/g' {} \;

# AssignRef
# $this->assignRef('pagination', $this->get('pagination'));
# $this->pagination = $this->get('pagination');
find -name 'view.html.php' -exec perl -i -p -e "s/assignRef\(\s*'([A-Za-z_0-9]+)'\s*,([^)]+)\)/\$1 =\$2/g" {} \;

#$limitstart = (int)JFactory::getApplication()->getInput()->get('limitstart', 0, '', 'int');

#    $db->setQuery($query);
#   // Check for a database error.
#   if ($db->getErrorNum()) {
#       JError::raiseWarning(500, $db->getErrorMsg());
#   }       

    $result = $db->loadResult();

#try
#{
#    $db->setQuery($query);
#    $result = $db->loadResult();
#}
#catch (RuntimeException $e)
#{
#    echo $e->getMessage();
#}


# JFactory::getEditor(); => Joomla\CMS\Editor\Editor::getInstance(JFactory::getApplication()->get('editor'));
find -name '*.php' -exec perl -i -p -e "s/JFactory::getEditor\(\)/Joomla\\CMS\\Editor\\Editor::getInstance(JFactory::getApplication()->get('editor'))/g" {} \;

# getInput()->get('post') => input->post->getArray()
find -name '*.php' -exec perl -i -p -e "s/getInput\(\)->get\('post'\)/input->post->getArray()/g" {} \;

# JFactory::getApplication()->getInput()->get('details_template', '', 'POST', 'STRING', JREQUEST_ALLOWRAW );
# JFactory::getApplication()->input->post->get('details_template', '', 'STRING', 'raw');
find -name '*.php' -exec perl -i -p -e "s/getInput\(\)->get\(([^,]+),([^,]+),\s*'POST'\s*,([^,]+)([,\)])/input->post->get(\$1,\$2,\$3\$4/ig" {} \;
find -name '*.php' -exec perl -i -p -e "s/getInput\(\)->get\(([^,]+),([^,]+),\s*'POST'\s*/input->post->get(\$1,\$2/ig" {} \;

# Specific getInput()->get('seperator',',', 'POST', 
find -name '*.php' -exec perl -i -p -e "s/getInput\(\)->get\(([^,]+),(','),\s*'POST'\s*,([^,]+)([,\)])/input->post->get(\$1,\$2,\$3\$4/ig" {} \;


find -name '*.php' -exec perl -i -p -e "s/'JREQUEST_ALLOWRAW', 'raw')/ig" {} \;
#find -name '*.php' -exec perl -i -p -e "s/getInput\(\)->get\('([^,]+),([^,]+),\s*'POST'\s*,([^,]+),\s*JREQUEST_ALLOWHTML\s*\)/input->post->get('\$1,\$2,\$3, 'raw')/ig" {} \;
find -name '*.php' -exec perl -i -p -e "s/'JREQUEST_ALLOWHTML', 'raw')/ig" {} \;


# Useless
# JSite::getRouter => JApplicationSite::getRouter();
find -name '*.php' -exec perl -i -p -e "s/JSite\:\:getRouter/JApplicationSite::getRouter/g" {} \;

# catslug -> catid
find -name '*.php' -exec perl -i -p -e "s/\>catslug/>catid/g" {} \;

stty sane



