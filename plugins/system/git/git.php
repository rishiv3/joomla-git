<?php
defined ( '_JEXEC' ) or die ( 'Restricted access' );
// Load up the git library
require_once('lib/git.php');
class plgSystemGit extends JPlugin {
	protected $autoloadLanguage = true;

	function onAfterRender() {

		// get plugin parameters
		$table = new JTableExtension(JFactory::getDbo());
		$table->load(array('element' => 'git'));

		// if commit and push times aren't set yet, set them
		if ($this->params['last_commit_time'] == "")
		{
			$time = date('Y-m-d H:i:s');
			$this->params['last_commit_time'] = $time;
			$table->set('params', $this->params->toString());
			$table->store();
		}
		if ($this->params['last_push_time'] == "")
		{
			$time = date('Y-m-d H:i:s');
			$this->params['last_push_time'] = $time;
			$table->set('params', $this->params->toString());
			$table->store();
		}

		// Setup variables
		$repo = new GitRepo(JPATH_ROOT);
		$repo->git_path = $this->params->get('git_path');
		$active_branch 	= $repo->active_branch();
		$status  		= $repo->status();
		$message 		= $this->params->get('git_message');
		$message 		= str_replace('[date]', date("Y-m-d"), $message);
		$message 		= str_replace('[time]', date("H-i"), $message);
		$remote  		= $this->params->get('git_remote');
		$remote_branch 	= $this->params->get('git_remote_branch');

		// compare current time to last commit time
		$current_time = date('Y-m-d H:i:s');
		$git_commit_diff = round((strtotime($current_time) - strtotime($this->params['last_commit_time']))/(60));

		//compare current time to last push time
		$git_push_diff = round((strtotime($current_time) - strtotime($this->params['last_push_time']))/(60));

		if ($git_commit_diff >= $this->params['git_commit_frequency'])
		{
			if ($status)
			{
				$commit_branch = trim($this->params['git_branch']);
				if ($commit_branch != '' && $commit_branch != $active_branch)
				{
					$repo->stash('save');
					$repo->checkout($this->params->get('git_branch'));
					$repo->stash('pop');
				}
				// Commit the changes to the branch
				$repo->add();
				$repo->commit($message);

				//update latest commit time
				$this->params['last_commit_time'] = $current_time;
				$table->set('params', $this->params->toString());
				$table->store();
			}
		}
		if ($this->params['git_push'] == 1)
		{
			if ($git_push_diff >= $this->params['git_push_frequency'])
			{
				if ($status)
				{
					$remote_branch = trim($this->params['git_remote_branch']);
					if ($remote_branch != '' && $remote_branch != $active_branch)
					{
						$repo->stash('save');
						$repo->checkout($this->params->get('git_branch'));
						$repo->stash('pop');
					}
					$repo->push($this->params['git_remote'], $this->params['git_remote_branch']);

					//update latest push time
					$this->params['last_push_time'] = $current_time;
					$table->set('params', $this->params->toString());
					$table->store();
				}
			}
		}
	}
}
?>