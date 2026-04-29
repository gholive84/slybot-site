# SlyBot Site — Instruções de Desenvolvimento

## Branch de desenvolvimento
- Sempre desenvolva na branch `claude/slybot-site-f3dPB`
- Faça commit e push nessa branch após cada mudança

## Deploy em produção (OBRIGATÓRIO)
Após cada push na branch de feature, **sempre** fazer o merge para `main` e push para ativar o deploy no GitHub Actions:

```bash
git checkout main
git merge claude/slybot-site-f3dPB
git push origin main
git checkout claude/slybot-site-f3dPB
```

O GitHub Actions faz o deploy automaticamente via SSH para a Hostinger quando há push na `main`.
