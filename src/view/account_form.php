<div id="main" class="mx-auto mt-3 col-md-11 col-lg-6">
    <form data-controller="FormController" action="/account/save" method="POST">
        <input type="hidden" name="uid" value="<?= $this->data->selected->uid ?? '0' ?>" />
        <div class="card shadow">
            <div class="card-header">
                <?php if ($this->data->state == 'edit'): ?>
                    <button class="btn btn-outline-primary" type="submit" name="save"><span class="bi bi-save"/></button>
                <?php else: ?>
                    <?php if ($this->data->selected): ?>
                        <a class="btn btn-outline-primary" href="/account/<?= $this->data->selected->uid ?? 0 ?>/delete"><span class="bi bi-trash"/></a>
                        <a class="btn btn-outline-primary" href="/account/<?= $this->data->selected->uid ?? 0?>/edit"><span class="bi bi-pencil"/></a>
                    <?php else: ?>
                        <a class="btn btn-outline-primary" href="/account/create"><span class="bi bi-plus-square"/></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h5 class="card-title">Mail Bridge</h5>

                <label class="form-label">Name</label>
                <input class="<?= $this->data->state == 'show' ? 'form-control-plaintext' : 'form-control' ?>"
                    required
                    name="name"
                    <?= $this->data->state == 'show' ? 'disabled' : '' ?>
                    value="<?= $this->data->selected->name ?? '' ?>" />

                <label class="form-label">Imap url</label>
                <input class="<?= $this->data->state == 'show' ? 'form-control-plaintext' : 'form-control' ?>"
                    required
                    name="imap_url"
                    placeholder="..."
                    <?= $this->data->state == 'show' ? 'disabled' : '' ?>
                    value="<?= $this->data->selected->_imap_url ?? '' ?>" />

                <label class="form-label">Smtp host</label>
                <input class="<?= $this->data->state == 'show' ? 'form-control-plaintext' : 'form-control' ?>"
                    required
                    name="smtp_host"
                    <?= $this->data->state == 'show' ? 'disabled' : '' ?>
                    value="<?= $this->data->selected->_smtp_host ?? '' ?>"  />

                <label class="form-label">Smtp port</label>
                <input class="<?= $this->data->state == 'show' ? 'form-control-plaintext' : 'form-control' ?>"
                    required
                    name="smtp_port"
                    <?= $this->data->state == 'show' ? 'disabled' : '' ?>
                    value="<?= $this->data->selected->_smtp_port ?? '' ?>"  />

                <label class="form-label">User</label>
                <input class="<?= $this->data->state == 'show' ? 'form-control-plaintext' : 'form-control' ?>"
                    required
                    name="user"
                    <?= $this->data->state == 'show' ? 'disabled' : '' ?>
                    value="<?= $this->data->selected->_user ?? '' ?>"  />

                <label class="form-label">Password</label>
                <input class="<?= $this->data->state == 'show' ? 'form-control-plaintext' : 'form-control' ?>"
                    required
                    name="password"
                    type="password"
                    <?= $this->data->state == 'show' ? 'disabled' : '' ?>
                    value="<?= $this->data->selected->_password ?? '' ?>"  />
            </div>
        </div>
    </form>
</div>