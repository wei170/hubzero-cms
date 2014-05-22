<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$canDo = BlogHelper::getActions('entry');

$text = ($this->task == 'edit' ? JText::_('Edit comment') : JText::_('New comment'));

JToolBarHelper::title(JText::_('Blog Manager') . ': ' . $text, 'blog.png');
if ($canDo->get('core.edit')) 
{
	JToolBarHelper::apply();
	JToolBarHelper::save();
	JToolBarHelper::spacer();
}
JToolBarHelper::cancel();
JToolBarHelper::spacer();
JToolBarHelper::help('comment');
?>
<script type="text/javascript">
function submitbutton(pressbutton) 
{
	var form = document.adminForm;

	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}

	// do field validation
	if ($('field-content').value == ''){
		alert("<?php echo JText::_('Error! You must fill in a comment!'); ?>");
	} else {
		submitform(pressbutton);
	}
}
</script>

<form action="index.php" method="post" name="adminForm" class="editform" id="item-form">
	<div class="col width-60 fltlft">
		<fieldset class="adminform">
			<legend><span><?php echo JText::_('Details'); ?></span></legend>

			<div class="input-wrap" data-hint="<?php echo JText::_('When marked anonymous a commenter\'s name and picture are hidden. No specific identifying information should be accessible by other users.'); ?>">
				<input class="option" type="checkbox" name="fields[anonymous]" id="field-anonymous" value="1"<?php if ($this->row->get('anonymous')) { echo ' checked="checked"'; } ?> />
				<label for="field-anonymous"><?php echo JText::_('Anonymous (commenter\'s name and picture are hidden)'); ?></label>
			</div>

			<div class="input-wrap">
				<label for="field-content"><?php echo JText::_('Content'); ?> <span class="required"><?php echo JText::_('required'); ?></span></label><br />
				<textarea name="fields[content]" id="field-content" cols="35" rows="15"><?php echo $this->escape(stripslashes($this->row->content('raw'))); ?></textarea>
			</div>
		</fieldset>
	</div>
	<div class="col width-40 fltrt">
		<table class="meta">
			<tbody>
				<tr>
					<th><?php echo JText::_('Created By'); ?>:</th>
					<td>
						<?php 
						$editor = JUser::getInstance($this->row->get('created_by'));
						echo $this->escape($editor->get('name')); 
						?>
						<input type="hidden" name="fields[created_by]" id="field-created_by" value="<?php echo $this->escape($this->row->get('created_by')); ?>" />
					</td>
				</tr>
				<tr>
					<th><?php echo JText::_('Created Date'); ?>:</th>
					<td>
						<?php echo $this->row->get('created'); ?>
						<input type="hidden" name="fields[created]" id="field-created" value="<?php echo $this->escape($this->row->get('created')); ?>" />
					</td>
				</tr>
				<tr>
					<th><?php echo JText::_('Entry #'); ?>:</th>
					<td>
						<?php echo $this->row->get('entry_id'); ?>
						<input type="hidden" name="fields[entry_id]" id="field-entry_id" value="<?php echo $this->escape($this->row->get('entry_id')); ?>" />
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<div class="clr"></div>

	<input type="hidden" name="fields[parent]" value="<?php echo $this->row->get('parent'); ?>" />
	<input type="hidden" name="fields[id]" value="<?php echo $this->row->get('id'); ?>" />
	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="save" />

	<?php echo JHTML::_('form.token'); ?>
</form>