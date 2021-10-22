<?php
use bronsted\Account;
?>
<div id="main" class="mx-auto mt-3 col-md-11 col-lg-6">
    <form data-controller="FormController" action="/account/save" method="POST">
        <input type="hidden" name="uid" value="<?= $this->data->selected->uid ?? '0' ?>" />
        <div class="card shadow">
            <div class="card-header">
                <?php if ($this->data->uiState == 'edit') : ?>
                    <button class="btn btn-outline-primary" type="submit" name="save"><span class="bi bi-save" /></button>
                <?php else : ?>
                    <?php if ($this->data->selected) : ?>
                        <a class="btn btn-outline-primary" href="/account/<?= $this->data->selected->uid ?? 0 ?>/delete"><span class="bi bi-trash" /></a>
                        <a class="btn btn-outline-primary" href="/account/<?= $this->data->selected->uid ?? 0 ?>/edit"><span class="bi bi-pencil" /></a>
                        <a class="btn btn-outline-primary" href="/account/<?= $this->data->selected->uid ?? 0 ?>/verify">Verify</a>
                    <?php else : ?>
                        <a class="btn btn-outline-primary" href="/account/create"><span class="bi bi-plus-square" /></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h5 class="card-title">Mail Bridge</h5>

                <div class="mb-2 row">
                    <label class="col-md-2 col-form-label">Name</label>
                    <div class="col-md-10">
                        <input class="<?= $this->data->uiState == 'show' ? 'form-control-plaintext' : 'form-control' ?>" required name="name" <?= $this->data->uiState == 'show' ? 'disabled' : '' ?> value="<?= $this->data->selected->name ?? '' ?>" />
                    </div>
                </div>

                <div class="mb-2 row">
                    <label class="col-md-2 col-form-label">Imap url</label>
                    <div class="col-md-10">
                        <input class="<?= $this->data->uiState == 'show' ? 'form-control-plaintext' : 'form-control' ?>" required name="imap_url" placeholder="{host:port/imap/ssl}INBOX" <?= $this->data->uiState == 'show' ? 'disabled' : '' ?> value="<?= $this->data->selected->imap_url ?? '' ?>" />
                    </div>
                </div>

                <div class="mb-2 row">
                    <label class="col-md-2 col-form-label">Smtp host</label>
                    <div class="col-md-10">
                        <input class="<?= $this->data->uiState == 'show' ? 'form-control-plaintext' : 'form-control' ?>" required name="smtp_host" <?= $this->data->uiState == 'show' ? 'disabled' : '' ?> value="<?= $this->data->selected->smtp_host ?? '' ?>" />
                    </div>
                </div>

                <div class="mb-2 row">
                    <label class="col-md-2 col-form-label">Smtp port</label>
                    <div class="col-md-10">
                        <input class="<?= $this->data->uiState == 'show' ? 'form-control-plaintext' : 'form-control' ?>" required name="smtp_port" <?= $this->data->uiState == 'show' ? 'disabled' : '' ?> value="<?= $this->data->selected->smtp_port ?? '' ?>" />
                    </div>
                </div>

                <div class="mb-2 row">
                    <label class="col-md-2 col-form-label">User</label>
                    <div class="col-md-10">
                        <input class="<?= $this->data->uiState == 'show' ? 'form-control-plaintext' : 'form-control' ?>" required name="user" <?= $this->data->uiState == 'show' ? 'disabled' : '' ?> value="<?= $this->data->selected->user ?? '' ?>" />
                    </div>
                </div>

                <div class="mb-2 row">
                    <label class="col-md-2 col-form-label">Password</label>
                    <div class="col-md-10">
                        <input class="<?= $this->data->uiState == 'show' ? 'form-control-plaintext' : 'form-control' ?>" required name="password" type="password" <?= $this->data->uiState == 'show' ? 'disabled' : '' ?> value="<?= $this->data->selected->password ?? '' ?>" />
                    </div>
                </div>

                <?php if (isset($this->data->selected)): ?>
                    <div class="mb-2 row">
                        <label class="col-md-2 col-form-label">Status</label>
                        <?php
                            $stateColor = 'bg-secondary';
                            if ($this->data->selected->state == Account::StateOk) {
                                $stateColor = 'bg-success';
                            }
                            elseif ($this->data->selected->state == Account::StateFail) {
                                $stateColor = 'bg-danger';
                            }
                        ?>
                        <label class="col-md-10 col-form-label">
                            <span class="badge rounded-pill <?= $stateColor ?>"><?= $this->data->selected->state_text ?></span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>