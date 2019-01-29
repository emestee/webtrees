<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Http\Controllers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module;
use Fisharebest\Webtrees\Module\FamilyTreeFavoritesModule;
use Fisharebest\Webtrees\Module\FamilyTreeNewsModule;
use Fisharebest\Webtrees\Module\FamilyTreeStatisticsModule;
use Fisharebest\Webtrees\Module\LoggedInUsersModule;
use Fisharebest\Webtrees\Module\ModuleBlockInterface;
use Fisharebest\Webtrees\Module\ModuleInterface;
use Fisharebest\Webtrees\Module\OnThisDayModule;
use Fisharebest\Webtrees\Module\ReviewChangesModule;
use Fisharebest\Webtrees\Module\SlideShowModule;
use Fisharebest\Webtrees\Module\UpcomingAnniversariesModule;
use Fisharebest\Webtrees\Module\UserFavoritesModule;
use Fisharebest\Webtrees\Module\UserMessagesModule;
use Fisharebest\Webtrees\Module\UserWelcomeModule;
use Fisharebest\Webtrees\Module\WelcomeBlockModule;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\User;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the user/tree's home page.
 */
class HomePageController extends AbstractBaseController
{
    private const DEFAULT_TREE_PAGE_BLOCKS = [
        'main' => [
            1 => FamilyTreeStatisticsModule::class,
            2 => FamilyTreeNewsModule::class,
            3 => FamilyTreeFavoritesModule::class,
            4 => ReviewChangesModule::class,
        ],
        'side' => [
            1 => WelcomeBlockModule::class,
            2 => SlideShowModule::class,
            3 => OnThisDayModule::class,
            4 => LoggedInUsersModule::class,
        ],
    ];

    private const DEFAULT_USER_PAGE_BLOCKS = [
        'main' => [
            1 => OnThisDayModule::class,
            2 => UserMessagesModule::class,
            3 => UserFavoritesModule::class,
        ],
        'side' => [
            1 => UserWelcomeModule::class,
            2 => SlideShowModule::class,
            3 => UpcomingAnniversariesModule::class,
            4 => LoggedInUsersModule::class,
        ],
    ];

    /**
     * Show a form to edit block config options.
     *
     * @param Request $request
     * @param Tree    $tree
     * @param User    $user
     *
     * @return Response
     */
    public function treePageBlockEdit(Request $request, Tree $tree, User $user): Response
    {
        $block_id = (int) $request->get('block_id');
        $block    = $this->treeBlock($request, $tree, $user);
        $title    = $block->title() . ' — ' . I18N::translate('Preferences');

        return $this->viewResponse('modules/edit-block-config', [
            'block'      => $block,
            'block_id'   => $block_id,
            'cancel_url' => route('tree-page', ['ged' => $tree->name()]),
            'title'      => $title,
            'tree'       => $tree,
        ]);
    }

    /**
     * Update block config options.
     *
     * @param Request $request
     * @param Tree    $tree
     * @param User    $user
     *
     * @return RedirectResponse
     */
    public function treePageBlockUpdate(Request $request, Tree $tree, User $user): RedirectResponse
    {
        $block    = $this->treeBlock($request, $tree, $user);
        $block_id = (int) $request->get('block_id');

        $block->saveBlockConfiguration($request, $block_id);

        return new RedirectResponse(route('tree-page', ['ged' => $tree->name()]));
    }

    /**
     * Load a block and check we have permission to edit it.
     *
     * @param Request $request
     * @param Tree    $tree
     * @param User    $user
     *
     * @return ModuleBlockInterface
     */
    private function treeBlock(Request $request, Tree $tree, User $user): ModuleBlockInterface
    {
        $block_id = (int) $request->get('block_id');

        $block = DB::table('block')
            ->where('block_id', '=', $block_id)
            ->where('gedcom_id', '=', $tree->id())
            ->whereNull('user_id')
            ->first();

        if ($block === null) {
            throw new NotFoundHttpException();
        }

        $module = Module::findByName($block->module_name);

        if (!$module instanceof ModuleBlockInterface) {
            throw new NotFoundHttpException();
        }

        if ($block->user_id !== $user->id() && !Auth::isAdmin()) {
            throw new AccessDeniedHttpException();
        }

        return $module;
    }

    /**
     * Show a form to edit block config options.
     *
     * @param Request $request
     * @param Tree    $tree
     * @param User    $user
     *
     * @return Response
     */
    public function userPageBlockEdit(Request $request, Tree $tree, User $user): Response
    {
        $block_id = (int) $request->get('block_id');
        $block    = $this->userBlock($request, $user);
        $title    = $block->title() . ' — ' . I18N::translate('Preferences');

        return $this->viewResponse('modules/edit-block-config', [
            'block'      => $block,
            'block_id'   => $block_id,
            'cancel_url' => route('user-page', ['ged' => $tree->name()]),
            'title'      => $title,
            'tree'       => $tree,
        ]);
    }

    /**
     * Update block config options.
     *
     * @param Request $request
     * @param Tree    $tree
     * @param User    $user
     *
     * @return RedirectResponse
     */
    public function userPageBlockUpdate(Request $request, Tree $tree, User $user): RedirectResponse
    {
        $block    = $this->userBlock($request, $user);
        $block_id = (int) $request->get('block_id');

        $block->saveBlockConfiguration($request, $block_id);

        return new RedirectResponse(route('user-page', ['ged' => $tree->name()]));
    }

    /**
     * Load a block and check we have permission to edit it.
     *
     * @param Request $request
     * @param User    $user
     *
     * @return ModuleBlockInterface
     */
    private function userBlock(Request $request, User $user): ModuleBlockInterface
    {
        $block_id = (int) $request->get('block_id');

        $block = DB::table('block')
            ->where('block_id', '=', $block_id)
            ->where('user_id', '=', $user->id())
            ->whereNull('gedcom_id')
            ->first();

        if ($block === null) {
            throw new NotFoundHttpException('This block does not exist');
        }

        $module = Module::findByName($block->module_name);

        if (!$module instanceof ModuleBlockInterface) {
            throw new NotFoundHttpException($block->module_name . ' is not a block');
        }

        $block_owner_id = (int) $block->user_id;

        if ($block_owner_id !== $user->id() && !Auth::isAdmin()) {
            throw new AccessDeniedHttpException('You are not allowed to edit this block');
        }

        return $module;
    }

    /**
     * Show a tree's page.
     *
     * @param Tree $tree
     *
     * @return Response
     */
    public function treePage(Tree $tree): Response
    {
        $has_blocks = DB::table('block')
            ->where('gedcom_id', '=', $tree->id())
            ->exists();

        if (!$has_blocks) {
            $this->checkDefaultTreeBlocksExist();

            // Copy the defaults
            (new Builder(DB::connection()))->from('block')->insertUsing(
                ['gedcom_id', 'location', 'block_order', 'module_name'],
                function (Builder $query) use ($tree): void {
                    $query
                        ->select([DB::raw($tree->id()), 'location', 'block_order', 'module_name'])
                        ->from('block')
                        ->where('gedcom_id', '=', -1);
                }
            );
        }

        return $this->viewResponse('tree-page', [
            'main_blocks' => $this->treeBlocks($tree->id(), 'main'),
            'side_blocks' => $this->treeBlocks($tree->id(), 'side'),
            'title'       => e($tree->title()),
            'meta_robots' => 'index,follow',
        ]);
    }

    /**
     * Load block asynchronously.
     *
     * @param Request $request
     * @param Tree    $tree
     *
     * @return Response
     */
    public function treePageBlock(Request $request, Tree $tree): Response
    {
        $block_id = $request->get('block_id');

        $block_id = (int) DB::table('block')
            ->where('block_id', '=', $block_id)
            ->where('gedcom_id', '=', $tree->id())
            ->value('block_id');

        $module = $this->getBlockModule($tree, $block_id);

        $html = view('layouts/ajax', [
            'content' => $module->getBlock($tree, $block_id, 'gedcom'),
        ]);

        return new Response($html);
    }

    /**
     * Show a form to edit the default blocks for new trees.
     *
     * @return Response
     */
    public function treePageDefaultEdit(): Response
    {
        $this->checkDefaultTreeBlocksExist();

        $main_blocks = $this->treeBlocks(-1,'main');
        $side_blocks = $this->treeBlocks(-1, 'side');

        $all_blocks  = $this->availableTreeBlocks();
        $title       = I18N::translate('Set the default blocks for new family trees');
        $url_cancel  = route('admin-control-panel');
        $url_save    = route('tree-page-default-update');

        return $this->viewResponse('edit-blocks-page', [
            'all_blocks'  => $all_blocks,
            'can_reset'   => false,
            'main_blocks' => $main_blocks,
            'side_blocks' => $side_blocks,
            'title'       => $title,
            'url_cancel'  => $url_cancel,
            'url_save'    => $url_save,
        ]);
    }

    /**
     * Save updated default blocks for new trees.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function treePageDefaultUpdate(Request $request): RedirectResponse
    {
        $main_blocks = (array) $request->get('main');
        $side_blocks = (array) $request->get('side');

        $this->updateTreeBlocks(-1, $main_blocks, $side_blocks);

        return new RedirectResponse(route('admin-control-panel'));
    }

    /**
     * Show a form to edit the blocks on a tree's page.
     *
     * @param Tree $tree
     *
     * @return Response
     */
    public function treePageEdit(Tree $tree): Response
    {
        $main_blocks = $this->treeBlocks($tree->id(), 'main');
        $side_blocks = $this->treeBlocks($tree->id(), 'side');

        $all_blocks  = $this->availableTreeBlocks();
        $title       = I18N::translate('Change the “Home page” blocks');
        $url_cancel  = route('tree-page', ['ged' => $tree->name()]);
        $url_save    = route('tree-page-update', ['ged' => $tree->name()]);

        return $this->viewResponse('edit-blocks-page', [
            'all_blocks'  => $all_blocks,
            'can_reset'   => true,
            'main_blocks' => $main_blocks,
            'side_blocks' => $side_blocks,
            'title'       => $title,
            'url_cancel'  => $url_cancel,
            'url_save'    => $url_save,
        ]);
    }

    /**
     * Save updated blocks on a tree's page.
     *
     * @param Request $request
     * @param Tree    $tree
     *
     * @return RedirectResponse
     */
    public function treePageUpdate(Request $request, Tree $tree): RedirectResponse
    {
        $defaults = (bool) $request->get('defaults');

        if ($defaults) {
            $main_blocks = $this->treeBlocks(-1, 'main')->all();
            $side_blocks = $this->treeBlocks(-1, 'side')->all();
        } else {
            $main_blocks = (array) $request->get('main');
            $side_blocks = (array) $request->get('side');
        }

        $this->updateTreeBlocks($tree->id(), $main_blocks, $side_blocks);

        return new RedirectResponse(route('tree-page', ['ged' => $tree->name()]));
    }

    /**
     * Show a users's page.
     *
     * @param User $user
     *
     * @return Response
     */
    public function userPage(User $user): Response
    {
        $has_blocks = DB::table('block')
            ->where('user_id', '=', $user->id())
            ->exists();

        if (!$has_blocks) {
            $this->checkDefaultUserBlocksExist();

            // Copy the defaults
            (new Builder(DB::connection()))->from('block')->insertUsing(
                ['user_id', 'location', 'block_order', 'module_name'],
                function (Builder $query) use ($user): void {
                    $query
                        ->select([DB::raw($user->id()), 'location', 'block_order', 'module_name'])
                        ->from('block')
                        ->where('user_id', '=', -1);
                }
            );
        }

        return $this->viewResponse('user-page', [
            'main_blocks' => $this->userBlocks($user->id(), 'main'),
            'side_blocks' => $this->userBlocks($user->id(), 'side'),
            'title'       => I18N::translate('My page'),
        ]);
    }

    /**
     * Load block asynchronously.
     *
     * @param Request $request
     * @param Tree    $tree
     * @param User    $user
     *
     * @return Response
     */
    public function userPageBlock(Request $request, Tree $tree, User $user): Response
    {
        $block_id = $request->get('block_id');

        $block_id = (int) DB::table('block')
            ->where('block_id', '=', $block_id)
            ->where('user_id', '=', $user->id())
            ->value('block_id');

        $module = $this->getBlockModule($tree, $block_id);

        $html = view('layouts/ajax', [
            'content' => $module->getBlock($tree, $block_id, 'user'),
        ]);

        return new Response($html);
    }

    /**
     * Show a form to edit the default blocks for new uesrs.
     *
     * @return Response
     */
    public function userPageDefaultEdit(): Response
    {
        $this->checkDefaultUserBlocksExist();

        $main_blocks = $this->userBlocks(-1, 'main');
        $side_blocks = $this->userBlocks(-1, 'side');
        $all_blocks  = $this->availableUserBlocks();
        $title       = I18N::translate('Set the default blocks for new users');
        $url_cancel  = route('admin-users');
        $url_save    = route('user-page-default-update');

        return $this->viewResponse('edit-blocks-page', [
            'all_blocks'  => $all_blocks,
            'can_reset'   => false,
            'main_blocks' => $main_blocks,
            'side_blocks' => $side_blocks,
            'title'       => $title,
            'url_cancel'  => $url_cancel,
            'url_save'    => $url_save,
        ]);
    }

    /**
     * Save the updated default blocks for new users.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function userPageDefaultUpdate(Request $request): RedirectResponse
    {
        $main_blocks = (array) $request->get('main');
        $side_blocks = (array) $request->get('side');

        $this->updateUserBlocks(-1, $main_blocks, $side_blocks);

        return new RedirectResponse(route('admin-control-panel'));
    }

    /**
     * Show a form to edit the blocks on the user's page.
     *
     * @param Tree $tree
     * @param User $user
     *
     * @return Response
     */
    public function userPageEdit(Tree $tree, User $user): Response
    {
        $main_blocks = $this->userBlocks($user->id(), 'main');
        $side_blocks = $this->userBlocks($user->id(), 'side');
        $all_blocks  = $this->availableUserBlocks();
        $title       = I18N::translate('Change the “My page” blocks');
        $url_cancel  = route('user-page', ['ged' => $tree->name()]);
        $url_save    = route('user-page-update', ['ged' => $tree->name()]);

        return $this->viewResponse('edit-blocks-page', [
            'all_blocks'  => $all_blocks,
            'can_reset'   => true,
            'main_blocks' => $main_blocks,
            'side_blocks' => $side_blocks,
            'title'       => $title,
            'url_cancel'  => $url_cancel,
            'url_save'    => $url_save,
        ]);
    }

    /**
     * Save the updted blocks on a user's page.
     *
     * @param Request $request
     * @param Tree    $tree
     * @param User    $user
     *
     * @return RedirectResponse
     */
    public function userPageUpdate(Request $request, Tree $tree, User $user): RedirectResponse
    {
        $defaults = (bool) $request->get('defaults');

        if ($defaults) {
            $main_blocks = $this->userBlocks(-1, 'main')->all();
            $side_blocks = $this->userBlocks(-1, 'side')->all();
        } else {
            $main_blocks = (array) $request->get('main');
            $side_blocks = (array) $request->get('side');
        }

        $this->updateUserBlocks($user->id(), $main_blocks, $side_blocks);

        return new RedirectResponse(route('user-page', ['ged' => $tree->name()]));
    }

    /**
     * Show a form to edit the blocks for another user's page.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function userPageUserEdit(Request $request): Response
    {
        $user_id     = (int) $request->get('user_id');
        $user        = User::find($user_id);
        $main_blocks = $this->userBlocks($user->id(), 'main');
        $side_blocks = $this->userBlocks($user->id(), 'side');
        $all_blocks  = $this->availableUserBlocks();
        $title       = I18N::translate('Change the blocks on this user’s “My page”') . ' - ' . e($user->getUserName());
        $url_cancel  = route('admin-users');
        $url_save    = route('user-page-user-update', ['user_id' => $user_id]);

        return $this->viewResponse('edit-blocks-page', [
            'all_blocks'  => $all_blocks,
            'can_reset'   => false,
            'main_blocks' => $main_blocks,
            'side_blocks' => $side_blocks,
            'title'       => $title,
            'url_cancel'  => $url_cancel,
            'url_save'    => $url_save,
        ]);
    }

    /**
     * Save the updated blocks for another user's page.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function userPageUserUpdate(Request $request): RedirectResponse
    {
        $user_id     = (int) $request->get('user_id');
        $main_blocks = (array) $request->get('main');
        $side_blocks = (array) $request->get('side');

        $this->updateUserBlocks($user_id, $main_blocks, $side_blocks);

        return new RedirectResponse(route('admin-control-panel'));
    }

    /**
     * Get a specific block.
     *
     * @param Tree $tree
     * @param int  $block_id
     *
     * @return ModuleBlockInterface
     * @throws NotFoundHttpException
     */
    private function getBlockModule(Tree $tree, int $block_id): ModuleBlockInterface
    {
        $active_blocks = Module::findByComponent('block', $tree, Auth::user());

        $module_name = DB::table('block')
            ->where('block_id', '=', $block_id)
            ->value('module_name');

        $block = $active_blocks->filter(function (ModuleInterface $module) use ($module_name): bool {
            return $module->name() === $module_name;
        })->first();

        if ($block === null) {
            throw new NotFoundHttpException('Block not found');
        }

        return $block;
    }

    /**
     * Get all the available blocks for a tree page.
     *
     * @return Collection|ModuleBlockInterface[]
     */
    private function availableTreeBlocks(): Collection
    {
        return Module::findByInterface(ModuleBlockInterface::class)
            ->filter(function (ModuleBlockInterface $block): bool {
                return $block->isTreeBlock();
            })
            ->mapWithKeys(function (ModuleInterface $block): array {
                return [$block->name() => $block];
            });
    }

    /**
     * Get all the available blocks for a user page.
     *
     * @return Collection|ModuleBlockInterface[]
     */
    private function availableUserBlocks(): Collection
    {
        return Module::findByInterface(ModuleBlockInterface::class)
            ->filter(function (ModuleBlockInterface $block): bool {
                return $block->isUserBlock();
            })
            ->mapWithKeys(function (ModuleInterface $block): array {
                return [$block->name() => $block];
            });
    }

    /**
     * Get the blocks for a specified tree.
     *
     * @param int    $tree_id
     * @param string $location "main" or "side"
     *
     * @return Collection|ModuleBlockInterface[]
     */
    private function treeBlocks(int $tree_id, string $location): Collection
    {
        $rows = DB::table('block')
            ->where('gedcom_id', '=', $tree_id)
            ->where('location', '=', $location)
            ->orderBy('block_order')
            ->pluck('module_name', 'block_id');

        return $this->filterActiveBlocks($rows, $this->availableTreeBlocks());
    }

    /**
     * Make sure that default blocks exist for a tree.
     *
     * @return void
     */
    private function checkDefaultTreeBlocksExist(): void
    {
        $has_blocks = DB::table('block')
            ->where('gedcom_id', '=', -1)
            ->exists();

        // No default settings?  Create them.
        if (!$has_blocks) {
            foreach (['main', 'side'] as $location ) {
                foreach (self::DEFAULT_TREE_PAGE_BLOCKS[$location] as $block_order => $class) {
                    $module_name = Module::findByClass($class)->name();

                    DB::table('block')->insert([
                        'gedcom_id'     => -1,
                        'location'    => $location,
                        'block_order' => $block_order,
                        'module_name' => $module_name,
                    ]);
                }
            }
        }
    }

    /**
     * Get the blocks for a specified user.
     *
     * @param int    $user_id
     * @param string $location "main" or "side"
     *
     * @return Collection|ModuleBlockInterface[]
     */
    private function userBlocks(int $user_id, string $location): Collection
    {
        $rows = DB::table('block')
            ->where('user_id', '=', $user_id)
            ->where('location', '=', $location)
            ->orderBy('block_order')
            ->pluck('module_name', 'block_id');

        return $this->filterActiveBlocks($rows, $this->availableUserBlocks());
    }

    /**
     * Make sure that default blocks exist for a user.
     *
     * @return void
     */
    private function checkDefaultUserBlocksExist(): void
    {
        $has_blocks = DB::table('block')
            ->where('user_id', '=', -1)
            ->exists();

        // No default settings?  Create them.
        if (!$has_blocks) {
            foreach (['main', 'side'] as $location ) {
                foreach (self::DEFAULT_USER_PAGE_BLOCKS[$location] as $block_order => $class) {
                    $module_name = Module::findByClass($class)->name();

                    DB::table('block')->insert([
                        'user_id'     => -1,
                        'location'    => $location,
                        'block_order' => $block_order,
                        'module_name' => $module_name,
                    ]);
                }
            }
        }
    }

    /**
     * Save the updated blocks for a tree.
     *
     * @param int   $tree_id
     * @param array $main_blocks
     * @param array $side_blocks
     *
     * @return void
     */
    private function updateTreeBlocks(int $tree_id, array $main_blocks, array $side_blocks)
    {
        $existing_block_ids = DB::table('block')
            ->where('gedcom_id', '=', $tree_id)
            ->pluck('block_id');

        // Deleted blocks
        foreach ($existing_block_ids as $existing_block_id) {
            if (!in_array($existing_block_id, $main_blocks) && !in_array($existing_block_id, $side_blocks)) {
                DB::table('block_setting')
                    ->where('block_id', '=', $existing_block_id)
                    ->delete();

                DB::table('block')
                    ->where('block_id', '=', $existing_block_id)
                    ->delete();
            }
        }

        $updates = [
            'main' => $main_blocks,
            'side' => $side_blocks,
        ];

        foreach ($updates as $location => $updated_blocks) {
            foreach ($updated_blocks as $block_order => $block_id) {
                if (is_numeric($block_id)) {
                    // Updated block
                    DB::table('block')
                        ->where('block_id', '=', $block_id)
                        ->update([
                            'block_order' => $block_order,
                            'location'    => $location,
                        ]);
                } else {
                    // New block
                    DB::table('block')->insert([
                        'gedcom_id'   => $tree_id,
                        'location'    => $location,
                        'block_order' => $block_order,
                        'module_name' => $block_id,
                    ]);
                }
            }
        }
    }

    /**
     * Save the updated blocks for a user.
     *
     * @param int   $user_id
     * @param array $main_blocks
     * @param array $side_blocks
     *
     * @return void
     */
    private function updateUserBlocks(int $user_id, array $main_blocks, array $side_blocks)
    {
        $existing_block_ids = DB::table('block')
            ->where('user_id', '=', $user_id)
            ->pluck('block_id');

        // Deleted blocks
        foreach ($existing_block_ids as $existing_block_id) {
            if (!in_array($existing_block_id, $main_blocks) && !in_array($existing_block_id, $side_blocks)) {
                DB::table('block_setting')
                    ->where('block_id', '=', $existing_block_id)
                    ->delete();

                DB::table('block')
                    ->where('block_id', '=', $existing_block_id)
                    ->delete();
            }
        }

        foreach ([
                     'main' => $main_blocks,
                     'side' => $side_blocks,
                 ] as $location => $updated_blocks) {
            foreach ($updated_blocks as $block_order => $block_id) {
                if (is_numeric($block_id)) {
                    // Updated block
                    DB::table('block')
                        ->where('block_id', '=', $block_id)
                        ->update([
                            'block_order' => $block_order,
                            'location'    => $location,
                        ]);
                } else {
                    // New block
                    DB::table('block')->insert([
                        'user_id'     => $user_id,
                        'location'    => $location,
                        'block_order' => $block_order,
                        'module_name' => $block_id,
                    ]);
                }
            }
        }
    }

    /**
     * Take a list of block names, and return block (module) objects.
     *
     * @param Collection $blocks
     * @param Collection $active_blocks
     *
     * @return Collection|ModuleBlockInterface[]
     */
    private function filterActiveBlocks(Collection $blocks, Collection $active_blocks): Collection
    {
        return $blocks->map(function (string $block_name) use ($active_blocks): ?ModuleBlockInterface {
            return $active_blocks->filter(function (ModuleInterface $block) use ($block_name): bool {
                return $block->name() === $block_name;
            })->first();
        })
            ->filter();
    }
}
